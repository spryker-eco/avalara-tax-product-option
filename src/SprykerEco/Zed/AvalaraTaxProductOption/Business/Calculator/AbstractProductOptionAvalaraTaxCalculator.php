<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapper;
use SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface;
use SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Service\AvalaraTaxProductOptionToUtilEncodingServiceInterface;

abstract class AbstractProductOptionAvalaraTaxCalculator implements ProductOptionAvalaraTaxCalculatorInterface
{
    /**
     * @var \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Service\AvalaraTaxProductOptionToUtilEncodingServiceInterface
     */
    protected $utilEncodingService;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface $moneyFacade
     * @param \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Service\AvalaraTaxProductOptionToUtilEncodingServiceInterface $utilEncodingService
     */
    public function __construct(
        AvalaraTaxProductOptionToMoneyFacadeInterface $moneyFacade,
        AvalaraTaxProductOptionToUtilEncodingServiceInterface $utilEncodingService
    ) {
        $this->moneyFacade = $moneyFacade;
        $this->utilEncodingService = $utilEncodingService;
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    abstract public function calculateTax(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer;

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\AvalaraTransactionLineTransfer[] $avalaraTransactionLineTransfers
     *
     * @return \Generated\Shared\Transfer\AvalaraTransactionLineTransfer[][]
     */
    protected function getProductOptionAvalaraTransactionLineTransfersIndexedByItemSkuAndProductOptionSku(ArrayObject $avalaraTransactionLineTransfers): array
    {
        $mappedProductOptionAvalaraTransactionLineTransfers = [];
        foreach ($avalaraTransactionLineTransfers as $avalaraTransactionLineTransfer) {
            if ($avalaraTransactionLineTransfer->getRef1OrFail() !== AvalaraLineItemMapper::PRODUCT_OPTION_AVALARA_LINE_TYPE) {
                continue;
            }

            $itemSku = $avalaraTransactionLineTransfer->getRef2OrFail();
            $productOptionSku = $avalaraTransactionLineTransfer->getItemCodeOrFail();
            $mappedProductOptionAvalaraTransactionLineTransfers[$itemSku][$productOptionSku] = $avalaraTransactionLineTransfer;
        }

        return $mappedProductOptionAvalaraTransactionLineTransfers;
    }

    /**
     * @param string $transactionLineDetails
     *
     * @return float
     */
    protected function sumTaxRateFromTransactionLineDetails(string $transactionLineDetails): float
    {
        $taxRateSum = 0.0;

        /** @var \Avalara\TransactionLineDetailModel[] $transactionLineDetailModels */
        $transactionLineDetailModels = $this->utilEncodingService->decodeJson($transactionLineDetails, false);
        foreach ($transactionLineDetailModels as $transactionLineDetailModel) {
            $taxRateSum += $transactionLineDetailModel->rate ?? 0.0;
        }

        return $this->convertToPercents($taxRateSum);
    }

    /**
     * @param float $number
     *
     * @return float
     */
    protected function convertToPercents(float $number): float
    {
        return $number * 100.0;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function setDefaultZeroTaxRateForProductOptions(ItemTransfer $itemTransfer): ItemTransfer
    {
        foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
            if (!$productOptionTransfer->getTaxRate() && !$productOptionTransfer->getSumTaxAmount()) {
                $productOptionTransfer
                    ->setTaxRate(0)
                    ->setSumTaxAmount(0);
            }
        }

        return $itemTransfer;
    }
}
