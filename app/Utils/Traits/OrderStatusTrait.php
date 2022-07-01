<?php

namespace App\Utils\Traits;

use App\Utils\Enums\OrderEnums;
use Illuminate\Support\Str;

/**
 * @package App\Models\Order
 * @method bool canCancelHandle()
 * @method bool canDeleteHandle()
 * @method bool canPayHandle()
 * @method bool canShipHandle()
 * @method bool canCommentHandle()
 * @method bool canConfirmHandle()
 * @method bool canRefundHandle()
 * @method bool canRebuyHandle()
 * @method bool canAftersaleHandle()
 * @method bool canAgreeRefundHandle()
 * @method bool isPayStatus()
 * @method bool isShipStatus()
 * @method bool isConfirmStatus()
 * @method bool isCancelStatus()
 * @method bool isAutoCancelStatus()
 * @method bool isRefundStatus()
 * @method bool isRefundConfirmStatus()
 * @method bool isAutoConfirmStatus()
 */
trait OrderStatusTrait
{
    public function __call(string $name, array $arguments)
    {
        if (Str::is('can*Handle', $name)) {
            if (is_null($this->order_status)) {
                throw new \Exception("order status is null when call method[$name]!");
            }
            $key = Str::of($name)
                ->replaceFirst('can', '')
                ->replaceLast('Handle', '')
                ->lower();
            return in_array($this->order_status, $this->canHandleMap[(string)$key]);
        } elseif (Str::is('is*Status', $name)) {
            if (is_null($this->order_status)) {
                throw new \Exception("order status is null when call method[$name]!");
            }
            $key = Str::of($name)
                ->replaceFirst('is', '')
                ->replaceLast('Status', '')
                ->snake()->upper()->prepend('STATUS');
            $status = (new \ReflectionClass(OrderEnums::class))->getConstant($key);
            return $this->order_status == $status;
        }

        return parent::__call($name, $arguments);
    }

    private $canHandleMap = [
        'cancel' => [OrderEnums::STATUS_CREATE],
        'pay' => [OrderEnums::STATUS_CREATE],
        'ship' => [OrderEnums::STATUS_PAY],
        'confirm' => [OrderEnums::STATUS_SHIP],
        'refund' => [OrderEnums::STATUS_PAY],
        // 同意退款
        'agreerefund' => [OrderEnums::STATUS_REFUND],
        'comment' => [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
        // 售后
        'aftersale' => [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
        // 回购
        'rebuy' => [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
        'delete' => [
            OrderEnums::STATUS_CANCEL,
            OrderEnums::STATUS_AUTO_CANCEL,
            OrderEnums::STATUS_ADMIN_CANCEL,
            OrderEnums::STATUS_REFUND_CONFIRM,
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
    ];

    public function isHadPaid()
    {
        return !in_array($this->status, [
            OrderEnums::STATUS_CREATE,
            OrderEnums::STATUS_CANCEL,
            OrderEnums::STATUS_AUTO_CANCEL,
            OrderEnums::STATUS_ADMIN_CANCEL,
        ]);
    }


    public function getCanHandleOptions()
    {
        return [
            'cancel' => $this->canCancelHandle(),
            'delete' => $this->canDeleteHandle(),
            'pay' => $this->canPayHandle(),
            'comment' => $this->canCommentHandle(),
            'confirm' => $this->canConfirmHandle(),
            'refund' => $this->canRefundHandle(),
            'aftersale' => $this->canAftersaleHandle(),
            'rebuy' => $this->canRebuyHandle()
        ];
    }
}
