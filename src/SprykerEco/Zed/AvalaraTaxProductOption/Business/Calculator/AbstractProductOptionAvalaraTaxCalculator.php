<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
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

        return $taxRateSum;
    }
}
