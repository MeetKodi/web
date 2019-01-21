<?

use kodi\frontend\assets\AppAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * The view file for the "order-coupon/index" action.
 *
 * @var \yii\web\View $this Current view instance.
 * @var $orderDetails array details about checked data before
 * @see \kodi\frontend\controllers\OrderCouponController::actionIndex()
 */

$this->title = Yii::t('frontend', 'Kodi Coupon Order');

$this->registerCssFile('/styles/order-coupon.css', ['depends' => AppAsset::class]);
$this->registerJsFile('@web/js/order-coupon.js', ['depends' => AppAsset::class]);
$quantity = $orderDetails['quantity'];
$color = $orderDetails['color'];
$price = $orderDetails['price'];
$imageSrc = "/images/coupon-{$color}.png";
?>

<div class="page-order page-regular">
    <div class="page-title">
        <div class="active"><?= Yii::t('frontend', 'Order details'); ?></div>
        <div class="title-delimiter"></div>
        <div class="passive"><?= Yii::t('frontend', 'Personal information'); ?></div>
        <div class="title-delimiter"></div>
        <div class="passive"><?= Yii::t('frontend', 'Payment'); ?></div>
    </div>

    <div class="page-content order-content">
        <?= Html::beginForm() ?>
        <div class="oc-left">
            <div class="ocl-name"><?= Yii::t('frontend', 'special sticker') ?></div>
            <div class="oc-sticker"><img src="/images/specialsticker.png"></div>
            <div class="oc-checkboxes">
                <div class="checkbox-container">
                    <?= Html::input('checkbox', 'sticker', null, ['id' => 'sticker', 'checked' => ArrayHelper::getValue($orderDetails, 'data.sticker')]); ?>
                    <?= Html::label(Yii::t('frontend', 'kodi point standard sticker'), 'sticker') ?>
                </div>
                <div class="checkbox-container">
                    <?= Html::input('checkbox', 'sticker_geo', null, ['id' => 'sticker-geo', 'checked' => ArrayHelper::getValue($orderDetails, 'data.sticker_geo')]); ?>
                    <?= Html::label(Yii::t('frontend', 'premium kodi point sticker with geolocation'), 'sticker-geo') ?>
                    <span class="oc-sticker-info">i</span>
                </div>
            </div>
        </div>
        <div class="oc-right">
            <div class="ocr-name"><?= Yii::t('frontend', 'coupon card') ?></div>
            <div class="oc-coupon">
                <img src="<?= $imageSrc ?>" class="oc-img" alt="Coupon">
            </div>
            <div class="oc-details">
                <div class="oc-price">
                    &euro;
                    <span id="oc-price">
                        <?= number_format($price, 2); ?>
                    </span>
                </div>
                <?= Yii::t('frontend', 'Choose your color') ?>
                <div class="order-color-choose">
                    <div class="o-c-blue<?= ($color === 'blue') ? ' active' : ''; ?>" data-color="blue" data-image="/images/coupon-blue.png"></div>
                    <div class="o-c-pink<?= ($color === 'pink') ? ' active' : ''; ?>" data-color="pink" data-image="/images/coupon-pink.png"></div>
                    <div class="o-c-yellow<?= ($color === 'yellow') ? ' active' : ''; ?>" data-color="yellow" data-image="/images/coupon-yellow.png"></div>
                </div>

                <?= Yii::t('frontend', 'Quantity') ?>
                <div class="order-quantity-choose">
                    <span class="oc-q-item<?= $quantity == 250 ? ' active' : ''; ?>">250</span>
                    <span class="oc-q-item<?= $quantity == 500 ? ' active' : ''; ?>">500</span>
                    <span class="oc-q-item<?= $quantity == 1000 ? ' active' : ''; ?>">1000</span>
                </div>
            </div>
            <div class="point-geo-info">
                <span class="pgi-close"></span>
                <span><?= Yii::t('frontend', 'Besides the free sticker, you get the geolocation service.') ?></span>
                <div><?= Yii::t('frontend', 'KodiPlus users will be able to receive notifications when they are nearby your business inviting them to enter and request the Kodi Cards.{br}One more reason to make your store known.', ['br' => '<br>']) ?></div>
            </div>
        </div>
        <div class="oc-proceed">
            <?= Html::hiddenInput('color', $color); ?>
            <?= Html::hiddenInput('quantity', $quantity); ?>
            <?= Html::submitButton(Yii::t('frontend', 'Next'), ['class' => 'btn-next']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>

    <a class="page-close" href="/"></a>
</div>