<?php

namespace kodi\frontend\controllers;

use kodi\common\enums\AlertType;
use kodi\common\enums\user\Status;
use kodi\common\models\user\User;
use kodi\frontend\models\forms\ResetPasswordRequestForm;
use kodi\frontend\models\forms\ResetPasswordForm;
use Yii;
use yii\helpers\Json;
use yii\web\Controller;

/**
 * Class `AuthController`
 * ======================
 *
 * This controller is responsible for user authentication.
 */
final class AuthController extends Controller
{
    /**
     * Activates user's account after manual registration
     *
     * @param $token
     * @return \yii\web\Response
     */
    public function actionActivate($token)
    {
        $tokenDecoded = Json::decode(base64_decode($token));
        $authToken = Yii::$app->security->findToken($tokenDecoded);
        if ($authToken) {
            $user = User::findOne(['id' => $authToken->user_id]);
            if ($user) {
                $user->status = Status::ACTIVE;
                $user->save(false);
                $authToken->delete();
                Yii::$app->session->addFlash(AlertType::SUCCESS, [
                    'message' => Yii::t('frontend', 'Your account was successfully activated! You can now login using your credentials.')
                ]);
            }
        } else {
            Yii::$app->session->addFlash(AlertType::ERROR, [
                'message' => Yii::t('frontend', 'An error occurred while trying to activate your account.')
            ]);
        }

        return $this->goHome();
    }

    /**
     * Resets user's password if token is set and it's valid
     * Requests user to enter email in order to reset their password
     *
     * @return string
     */
    public function actionPasswordReset()
    {
        $token = Yii::$app->request->get('token');
        $model = new ResetPasswordRequestForm();
        $isRecovery = false;

        if (!empty($token)) {
            $model = new ResetPasswordForm();
            $tokenDecoded = Json::decode(base64_decode($token));
            $authToken = Yii::$app->security->findToken($tokenDecoded);
            if ($authToken) {
                $user = User::findOne(['id' => $authToken->user_id]);
                if ($user) {
                    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                        $user->password = Yii::$app->getSecurity()->generatePasswordHash($model->password);
                        $user->save(false);

                        $authToken->delete();
                        Yii::$app->session->addFlash(AlertType::SUCCESS, [
                            'message' => Yii::t('frontend', 'Your password was successfully changed.')
                        ]);
                        return $this->goHome();
                    }
                }
            } else {
                Yii::$app->session->addFlash(AlertType::ERROR, [
                    'message' => Yii::t('frontend', 'Invalid token.')
                ]);
                return $this->goHome();
            }

            $isRecovery = true;

        } else {
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($model->sendEmail()) {
                    Yii::$app->session->addFlash(AlertType::SUCCESS, [
                        'message' => Yii::t('frontend', 'We sent instructions to your email address.'),
                    ]);
                } else {
                    Yii::$app->session->addFlash(AlertType::ERROR, [
                        'title' => Yii::t('frontend', 'Recovery failed!'),
                        'message' => Yii::t('frontend', 'Could not find your account.'),
                    ]);
                }
            }
        }

        // Render page
        return $this->render('password-reset', [
            'model' => $model,
            'isRecovery' => $isRecovery,
        ]);
    }
}
