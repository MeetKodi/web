<?php

namespace kodi\api\controllers;

use Exception;
use kodi\api\models\auth\SignIn;
use kodi\api\models\auth\SignUp;
use kodi\common\enums\Status;
use kodi\common\enums\user\Role;
use kodi\common\enums\user\TokenType;
use kodi\common\models\device\Device;
use kodi\common\models\user\AuthToken;
use kodi\common\models\user\Profile;
use kodi\common\models\user\User;
use kodi\frontend\models\forms\ResetPasswordRequestForm;
use Yii;
use yii\base\ErrorException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Class AuthController
 * ====================
 *
 * This class is created specifically for device (mobile or kiosk) authentication.
 * In order to authenticate, user has to provide it's
 *
 * @package app\controllers
 */
class AuthController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'sign-in' => ['post'],
                'sign-up' => ['post'],
                'sign-out' => ['post'],
                'token-refresh' => ['post'],
                'password-reset' => ['post'],
            ],
        ];

        return $behaviors;
    }

    /**
     * Signs third part (Mobile app or Kiosk app) in by issuing access token.
     * The third part should send following data:
     * [
     *  'email' => 'registered user email',
     *  'password' => 'registered user password',
     *  'uuid' => 'unique device id the action performs from',
     *  'type' => 'device type',
     *  'name' => 'device name', // not necessary
     * ]
     *
     * @return string|array
     * @throws ForbiddenHttpException
     */
    public function actionSignIn() {
        $data = Yii::$app->getRequest()->getBodyParams();
        $model = new SignIn();
        $model->load($data, '');
        if ($model->validate()) {
            // In case email/password are valid, we have to check for device uuid.
            // If there is no such a device, we have to create it
            $device = Device::findOne(['uuid' => $model->uuid]);
            if (!empty($device)) {
                // Remove all previous access tokens if exist for this device
                AuthToken::deleteAll(['device_id' => $device->id]);
                if ($device->status !== Status::ACTIVE) {
                    throw new ForbiddenHttpException('Your device is suspended.');
                }
            } else {
                // Register the device in DB
                $device = new Device([
                    'uuid' => $model->uuid,
                    'user_id' => $model->_identity->getId(),
                    'type' => ucfirst($model->type),
                    'name' => ArrayHelper::getValue($data, 'name'),
                    'status' => Status::ACTIVE,
                ]);
                $device->save(false);
            }

            // Authenticate the device by issuing an access token
            $expiresIn = ArrayHelper::getValue(Yii::$app->params, 'security.token.access.expiration');

            return Yii::$app->security->generateToken($device->user_id, TokenType::ACCESS, $device->id, $expiresIn, true, true);

        } else {
            $response = Yii::$app->response;
            $response->statusCode = 404;
            $response->data = $model->errors;
            return $response;
        }
    }

    public function actionSignInExternal()
    {
        $data = Yii::$app->getRequest()->getBodyParams();
    }

    /**
     * Signs user up, not third part
     * Note. Unlike sign-in method, this one is for users who use third party apps not for devices
     * Devices are registering automatically with sign-in method
     *
     * @return string|\yii\console\Response|\yii\web\Response
     */
    public function actionSignUp() {
        $data = Yii::$app->getRequest()->getBodyParams();
        $confirmationRequired = Yii::$app->settings->get('system_email_confirmation_require');
        $successMessage = $confirmationRequired ? Yii::t('api', 'New user account is successfully created. Please confirm your email address.') : Yii::t('api', 'Thank you for your registration. Now you can sign in using your credentials.');
        $model = new SignUp();
        $model->load($data, '');
        if ($model->validate()) {
            try {
                // Create DB records
                User::getDb()->transaction(function () use ($model, $confirmationRequired) {
                    $user = new User([
                        'status' => $confirmationRequired ? Status::INACTIVE : Status::ACTIVE,
                        'role' => Role::CUSTOMER,
                        'email' => $model->email,
                        'password' => $model->password,
                    ]);
                    if (!$user->save()) {
                        throw new Exception($user->errors);
                    }

                    $profile = new Profile([
                        'name' => $model->name ?: explode('@', $model->email)[0],
                    ]);
                    $profile->link('user', $user);

                    if ($confirmationRequired) {
                        // Send welcome email with confirmation
                        $token = Yii::$app->security->generateToken($user->id, TokenType::EMAIL_CONFIRMATION);
                        $token = base64_encode(Json::encode($token));
                        $confirmationUrl = str_replace('api.', '', Url::to(["/auth/activate/$token"], true));

                        Yii::$app->mailer->compose('welcome', [
                            'user' => $user,
                            'confirmationUrl' => $confirmationUrl,
                        ])
                            ->setFrom(Yii::$app->settings->get('system_email_sender'))
                            ->setTo($user->email)
                            ->setSubject(Yii::t('api', 'KODI: Account activation'))
                            ->send();
                    }

                    return true;
                });

                // Show success notification
                return $successMessage;

            } catch (\Exception $exception) {
                $response = Yii::$app->response;
                $response->statusCode = 400;
                $response->data = $exception;
                return $response;
            }
        } else {
            $response = Yii::$app->response;
            $response->statusCode = 400;
            $response->data = $model->errors;
            return $response;
        }

    }

    /**
     * Removes existing access token.
     * Basically uses when a third part logs out.
     * @return bool|null
     * @throws BadRequestHttpException
     */
    public function actionSignOut() {
        $tokenData = Yii::$app->getRequest()->getBodyParams();
        if (empty($tokenData['id']) || empty($tokenData['token'])) {
            throw new BadRequestHttpException('No token provided.');
        }

        return Yii::$app->security->revokeToken($tokenData);
    }

    /**
     * Refreshes existing token.
     * Basically uses by a third part to refresh access token that's about to expire.
     * In order to keep the third part authenticated
     *
     * @return null|array
     * @throws BadRequestHttpException
     */
    public function actionTokenRefresh() {
        $tokenData = Yii::$app->getRequest()->getBodyParams();
        if (empty($tokenData['id']) || empty($tokenData['token'])) {
            throw new BadRequestHttpException('No token provided.');
        }

        $expiresIn = ArrayHelper::getValue(Yii::$app->params, 'security.token.access.expiration');

        return Yii::$app->security->refreshToken($tokenData, $expiresIn, true);
    }

    /**
     * @return string|\yii\console\Response|\yii\web\Response
     * @throws BadRequestHttpException
     * @throws ErrorException
     */
    public function actionPasswordReset() {
        $email = ArrayHelper::getValue(Yii::$app->getRequest()->getBodyParams(), 'email');
        if (empty($email)) {
            throw new BadRequestHttpException('No email provided.');
        }

        $model = new ResetPasswordRequestForm(['email' => $email]);
        if ($model->validate()) {
            if ($model->sendEmail()) {
                return Yii::t('api', 'We sent instructions to your email address.');
            }

            throw new ErrorException('An error occurred while sending email.');
        } else {
            $response = Yii::$app->response;
            $response->statusCode = 400;
            $response->data = $model->errors;
            return $response;
        }
    }
}
