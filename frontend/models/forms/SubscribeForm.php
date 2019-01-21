<?php
namespace kodi\frontend\models\forms;

use sammaye\mailchimp\exceptions\MailChimpException;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Password reset form
 */
class SubscribeForm extends Model
{
    public $email;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
        ];
    }

    /**
     * Subscribes to MailChimp list
     *
     * @param null $listId
     * @return array
     */
    public function subscribe($listId = null)
    {
        if (!$listId) {
            $listId = ArrayHelper::getValue(Yii::$app->params, 'services.mailChimp.lists.EARLY_ACCESS');
        }
        $params = [
            'email_address' => $this->email,
            'status' => 'subscribed',
            'language' => Yii::$app->language,
        ];
        $result = [
            'success' => true,
            'message' => Yii::t('frontend', 'You have been successfully subscribed to our newsletter.')
        ];
        try {
            Yii::$app->mailchimp->post("/lists/{$listId}/members", $params);
        } catch (MailChimpException $exception) {
            $result['success'] = false;
            $result['message'] = $exception->getMessage();
        }

        return $result;
    }
}
