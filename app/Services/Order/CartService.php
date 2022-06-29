<?php

namespace App\Services\Order;

use App\CodeResponse;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsProduct;
use App\Models\Order\Cart;
use App\Services\BaseService;
use App\Services\Goods\GoodsService;
use App\Services\Promotion\GrouponService;

class CartService extends BaseService
{
    public function add(int $userId, int $goodsId, int $productId, int $number)
    {
        list($goods, $product) = $this->getGoodsInfo($goodsId, $productId, $number);
        $cartProduct = $this->getCartProduct($userId, $goodsId, $productId);
        if (is_null($cartProduct)) {
            return $this->newCart($userId, $goods, $product, $number);
        } else {
            $number = $cartProduct->number + $number;
            return $this->editCart($cartProduct, $product, $number);
        }
    }

    public function fastAdd(int $userId, int $goodsId, int $productId, int $number)
    {
        list($goods, $product) = $this->getGoodsInfo($goodsId, $productId, $number);
        $cartProduct = $this->getCartProduct($userId, $goodsId, $productId);
        if (is_null($cartProduct)) {
            return $this->newCart($userId, $goods, $product, $number);
        } else {
            return $this->editCart($cartProduct, $product, $number);
        }
    }

    public function getGoodsInfo(int $goodsId, int $productId, int $number)
    {
        $goods = GoodsService::getInstance()->getGoods($goodsId);
        if (is_null($goods) || !$goods->is_on_sale) {
            $this->throwBusinessException(CodeResponse::GOODS_UNSHELVE);
        }

        $product = GoodsService::getInstance()->getGoodsProduct($productId);
        if (is_null($product) || $number > $product->number) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }

        return [$goods, $product];
    }

    public function getCartProduct(int $userId, int $goodsId, int $productId)
    {
        return Cart::query()
            ->where('user_id', $userId)
            ->where('goods_id', $goodsId)
            ->where('product_id', $productId)
            ->first();
    }

    public function newCart(int $userId, Goods $goods, GoodsProduct $product, int $number)
    {
        if ($number > $product->number) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }
        $cart = Cart::new();
        $cart->goods_id = $goods->id;
        $cart->product_id = $product->id;
        $cart->goods_sn = $goods->goods_sn;
        $cart->goods_name = $goods->name;
        $cart->pic_url = $product->url ?: $goods->pic_url;
        $cart->price = $product->price;
        $cart->specifications = $product->specifications;
        $cart->user_id = $userId;
        $cart->checked = true;
        $cart->number = $number;
        $cart->save();
        return $cart;
    }

    public function editCart(Cart $existCart, GoodsProduct $product, int $number)
    {
        if ($number > $product->number) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }
        $existCart->number = $number;
        $existCart->save();
        return $existCart;
    }

    public function countCartProduct(int $userId)
    {
        return Cart::query()->where('user_id', $userId)->sum('number');
    }

    public function getValidCartList(int $userId)
    {
        $cartList = $this->getCartList($userId);
        $goodsIds = $cartList->pluck('goods_id')->toArray();
        $goodsList = GoodsService::getInstance()->getGoodsListByIds($goodsIds)->keyBy('id');

        $invalidCartIds = [];
        // &$invalidCartIds: 参数，在闭包中被赋值，并且在外部能使用，需在参数前加&
        $validCartList = $cartList->filter(function (Cart $cart) use ($goodsList, &$invalidCartIds) {
            /** @var Goods $goods */
            $goods = $goodsList->get($cart->goods_id);
            $isValid = !is_null($goods) && $goods->is_on_sale;
            if (!$isValid) {
                $invalidCartIds[] = $cart->id;
            }
            return $isValid;
        });
        $this->deleteByIds($invalidCartIds);

        return $validCartList;
    }

    public function getCartList(int $userId)
    {
        return Cart::query()->where('user_id', $userId)->get();
    }

    public function getCheckedCartList(int $userId)
    {
        return Cart::query()->where('user_id', $userId)->where('checked', 1)->get();
    }

    public function getCart(int $id, $columns = ['*'])
    {
        return Cart::query()->find($id, $columns);
    }

    public function updateChecked(int $userId, int $productIds, bool $isChecked)
    {
        return Cart::query()
            ->where('user_id')
            ->whereIn('product_id', $productIds)
            ->update(['checked' => $isChecked]);
    }

    public function delete(int $userId, array $productIds)
    {
        return Cart::query()->where('user_id', $userId)->whereIn('product_id', $productIds)->delete();
    }

    public function deleteByIds(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }
        return Cart::query()->whereIn('id', $ids)->delete();
    }

    public function getPreorderCartList(int $userId, int $cartId)
    {
        if (empty($cartId)) {
            $cartList = $this->getCheckedCartList($userId);
        } else {
            $cart = $this->getCart($cartId);
            if (is_null($cart)) {
                $this->throwBadArgumentValue();
            }
            $cartList = collect([$cart]);
        }
        return $cartList;
    }

    public function getCartPriceCutGroupon($cartList, $grouponRulesId, &$grouponPrice)
    {
        $grouponRules = GrouponService::getInstance()->getGrouponRule($grouponRulesId);
        $cartPrice = 0;
        /** @var Cart $cart */
        foreach ($cartList as $cart) {
            if ($grouponRules && $grouponRules->goods_id == $cart->goods_id) {
                $grouponPrice = bcmul($grouponRules->discount, $cart->number, 2);
                $price = bcsub($cart->price, $grouponRules->discount, 2);
            } else {
                $price = $cart->price;
            }
            $price = bcmul($price, $cart->number, 2);
            $cartPrice = bcadd($cartPrice, $price, 2);
        }
        return $cartPrice;
    }
}
