<?php
/**
 * Created by PhpStorm.
 * User: ngocnh
 * Date: 06/08/2018
 * Time: 14:04
 */

namespace SmartOSC\MultiCoupon\Model\ResourceModel\Rule;

use Magento\Quote\Model\Quote\Address;

class Collection extends \Magento\SalesRule\Model\ResourceModel\Rule\Collection
{
    /**
     * Filter collection by specified website, customer group, coupon code, date.
     * Filter collection to use only active rules.
     * Involved sorting by sort_order column.
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param array $couponCode
     * @param string|null $now
     * @param Address $address allow extensions to further filter out rules based on quote address
     * @use $this->addWebsiteGroupDateFilter()
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return $this
     */
    public function setValidationFilter(
        $websiteId,
        $customerGroupId,
        $couponCode = [],
        $now = null,
        Address $address = null
    ) {
        if (!$this->getFlag('validation_filter')) {
            /* We need to overwrite joinLeft if coupon is applied */
            $this->getSelect()->reset();
            $this->getSelect()->from(['main_table' => $this->getMainTable()]);

            $this->addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now);
            $select = $this->getSelect();

            $connection = $this->getConnection();
            if (!empty($couponCode)) {
                $select->joinLeft(
                    ['rule_coupons' => $this->getTable('salesrule_coupon')],
                    $connection->quoteInto(
                        'main_table.rule_id = rule_coupons.rule_id AND main_table.coupon_type != ?',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                    ),
                    ['code']
                );

                $noCouponWhereCondition = $connection->quoteInto(
                    'main_table.coupon_type = ? ',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                );

                $autoGeneratedCouponCondition = [
                    $connection->quoteInto(
                        "main_table.coupon_type = ?",
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
                    ),
                    $connection->quoteInto(
                        "rule_coupons.type = ?",
                        \Magento\SalesRule\Api\Data\CouponInterface::TYPE_GENERATED
                    ),
                ];

                $orWhereConditions = [
                    "(" . implode($autoGeneratedCouponCondition, " AND ") . ")",
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 1 AND rule_coupons.type = 1)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 0 AND rule_coupons.type = 0)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                    ),
                ];

                $andWhereConditions = [
                    $connection->quoteInto(
                        'rule_coupons.code IN (?)',
                        $couponCode
                    ),
                    $connection->quoteInto(
                        '(rule_coupons.expiration_date IS NULL OR rule_coupons.expiration_date >= ?)',
                        $this->_date->date()->format('Y-m-d')
                    ),
                ];

                $orWhereCondition = implode(' OR ', $orWhereConditions);
                $andWhereCondition = implode(' AND ', $andWhereConditions);

                $select->where(
                    $noCouponWhereCondition . ' OR ((' . $orWhereCondition . ') AND ' . $andWhereCondition . ')'
                );
            } else {
                $this->addFieldToFilter(
                    'main_table.coupon_type',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                );
            }
            $this->setOrder('sort_order', self::SORT_ORDER_ASC);
            $this->setFlag('validation_filter', true);
        }

        return $this;
    }
}