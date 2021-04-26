<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\AvalaraTaxProductOption\Business;

use Codeception\Test\Unit;
use Generated\Shared\DataBuilder\AddressBuilder;
use Generated\Shared\DataBuilder\AvalaraCreateTransactionRequestBuilder;
use Generated\Shared\DataBuilder\AvalaraCreateTransactionResponseBuilder;
use Generated\Shared\DataBuilder\AvalaraTransactionBuilder;
use Generated\Shared\DataBuilder\CalculableObjectBuilder;
use Generated\Shared\DataBuilder\ItemBuilder;
use Generated\Shared\DataBuilder\ProductOptionBuilder;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\AvalaraTransactionLineTransfer;
use Generated\Shared\Transfer\AvalaraTransactionTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapper;

class AvalaraTaxProductOptionFacadeTest extends Unit
{
    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     */
    protected const PRICE_MODE_GROSS = 'GROSS_MODE';

    protected const TEST_PRODUCT_OPTION_SKU = 'test-product-option-sku';
    protected const TEST_ITEM_SKU = 'test-item-sku';
    protected const TEST_PRODUCT_OPTION_PRICE = 111;
    protected const TEST_ZIP_CODE = '48201';
    protected const TEST_PRODUCT_OPTION_TAX = 18.829999999999998;

    /**
     * @var \SprykerEcoTest\Zed\AvalaraTaxProductOption\AvalaraTaxProductOptionBusinessTester
     */
    protected $tester;

    /**
     * @return void
     */
    public function testexpandAvalaraCreateTransactionRequestWithProductOptionWillExpandAvalaraTransactionRequestTransferWithProductOptionLines(): void
    {
        // Arrange
        $calculableObjectTransfer = $this->createCalculableObjectTransfer();

        $avalaraCreateTransactionRequestTransfer = (new AvalaraCreateTransactionRequestBuilder())
            ->withTransaction()
            ->build();

        // Act
        $avalaraCreateTransactionRequestTransfer = $this->tester->getFacade()->expandAvalaraCreateTransactionRequestWithProductOptions(
            $avalaraCreateTransactionRequestTransfer,
            $calculableObjectTransfer
        );

        // Assert
        $this->assertGreaterThanOrEqual(1, $avalaraCreateTransactionRequestTransfer->getTransaction()->getLines()->count());
        $avalaraLineItemTransfer = $this->findProductOptionAvalaraLineItemTransfer(
            static::TEST_PRODUCT_OPTION_SKU,
            $avalaraCreateTransactionRequestTransfer
        );
        $this->assertNotNull($avalaraLineItemTransfer);
        $this->assertTrue($avalaraLineItemTransfer->getAmount()->equals(1.11));
    }

    /**
     * @return void
     */
    public function testCalculateProductOptionTaxWillAddTaxRateAndTaxSumWithQuoteLevelShipment(): void
    {
        // Arrange
        $shippingAddressTransfer = (new AddressBuilder())->build();
        $calculableObjectTransfer = $this->createCalculableObjectTransfer()->setShippingAddress($shippingAddressTransfer);

        $avalaraTransactionTransfer = $this->createAvalaraTransactionTransfer();
        $avalaraCreateTransactionResponseTransfer = (new AvalaraCreateTransactionResponseBuilder())->build();
        $avalaraCreateTransactionResponseTransfer->setTransaction($avalaraTransactionTransfer);

        // Act
        $calculableObjectTransfer = $this->tester->getFacade()->calculateProductOptionTax(
            $calculableObjectTransfer,
            $avalaraCreateTransactionResponseTransfer
        );

        // Assert
        $productOptionTransfer = $this->findProductOptionTransfer(static::TEST_PRODUCT_OPTION_SKU, $calculableObjectTransfer);
        $this->assertSame(6, $productOptionTransfer->getTaxRate());
        $this->assertSame(1883, $productOptionTransfer->getSumTaxAmount());
    }

    /**
     * @return void
     */
    public function testCalculateProductOptionTaxWillAddTaxRateAndTaxSumWithItemLevelShipment(): void
    {
        // Arrange
        $calculableObjectTransfer = $this->createCalculableObjectTransfer(true);

        $avalaraTransactionTransfer = $this->createAvalaraTransactionTransfer();
        $avalaraCreateTransactionResponseTransfer = (new AvalaraCreateTransactionResponseBuilder())->build();
        $avalaraCreateTransactionResponseTransfer->setTransaction($avalaraTransactionTransfer);

        // Act
        $calculableObjectTransfer = $this->tester->getFacade()->calculateProductOptionTax(
            $calculableObjectTransfer,
            $avalaraCreateTransactionResponseTransfer
        );

        // Assert
        $productOptionTransfer = $this->findProductOptionTransfer(static::TEST_PRODUCT_OPTION_SKU, $calculableObjectTransfer);
        $this->assertSame(6, $productOptionTransfer->getTaxRate());
        $this->assertSame(1883, $productOptionTransfer->getSumTaxAmount());
    }

    /**
     * @param string $productOptionSku
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer|null
     */
    protected function findProductOptionAvalaraLineItemTransfer(
        string $productOptionSku,
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
    ): ?AvalaraLineItemTransfer {
        foreach ($avalaraCreateTransactionRequestTransfer->getTransaction()->getLines() as $avalaraLineItemTransfer) {
            if ($avalaraLineItemTransfer->getItemCode() === $productOptionSku) {
                return $avalaraLineItemTransfer;
            }
        }

        return null;
    }

    /**
     * @param string $productOptionSku
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\ProductOptionTransfer|null
     */
    protected function findProductOptionTransfer(string $productOptionSku, CalculableObjectTransfer $calculableObjectTransfer): ?ProductOptionTransfer
    {
        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
                if ($productOptionTransfer->getSku() === $productOptionSku) {
                    return $productOptionTransfer;
                }
            }
        }

        return null;
    }

    /**
     * @param bool $includeItemLevelShipment
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    protected function createCalculableObjectTransfer(bool $includeItemLevelShipment = false): CalculableObjectTransfer
    {
        $productOptionTransfer = (new ProductOptionBuilder([
            ProductOptionTransfer::SKU => static::TEST_PRODUCT_OPTION_SKU,
            ProductOptionTransfer::SUM_PRICE => static::TEST_PRODUCT_OPTION_PRICE,
            ProductOptionTransfer::SUM_DISCOUNT_AMOUNT_AGGREGATION => 0,
            ProductOptionTransfer::QUANTITY => 1,
        ]))->build();

        $itemBuilder = (new ItemBuilder([ItemTransfer::SKU => static::TEST_ITEM_SKU]));

        if ($includeItemLevelShipment) {
            $itemBuilder->withShipment([
                ShipmentTransfer::SHIPPING_ADDRESS => (new AddressBuilder([
                    AddressTransfer::ZIP_CODE => static::TEST_ZIP_CODE,
                ]))->build(),
            ]);
        }

        $itemTransfer = $itemBuilder->build();
        $itemTransfer->addProductOption($productOptionTransfer);

        $calculableObjectTransfer = (new CalculableObjectBuilder([
            CalculableObjectTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
        ]))->build();

        return $calculableObjectTransfer->addItem($itemTransfer);
    }

    /**
     * @return \Generated\Shared\Transfer\AvalaraTransactionTransfer
     */
    protected function createAvalaraTransactionTransfer(): AvalaraTransactionTransfer
    {
        return (new AvalaraTransactionBuilder([
            AvalaraTransactionTransfer::ADDRESSES => '[{"id":0,"transactionId":0,"boundaryLevel":"Zip5","line1":"Seeburger Str., 270, Block B","line2":"270","line3":"Block B","city":"Detroit","region":"MI","postalCode":"48201","country":"US","taxRegionId":4019220,"latitude":"42.347989","longitude":"-83.061514"}]',
        ]))->withLine([
            AvalaraTransactionLineTransfer::QUANTITY => 1,
            AvalaraTransactionLineTransfer::ITEM_CODE => static::TEST_PRODUCT_OPTION_SKU,
            AvalaraTransactionLineTransfer::REF1 => AvalaraLineItemMapper::PRODUCT_OPTION_AVALARA_LINE_TYPE,
            AvalaraTransactionLineTransfer::REF2 => static::TEST_ITEM_SKU,
            AvalaraTransactionLineTransfer::TAX => new Decimal(static::TEST_PRODUCT_OPTION_TAX),
            AvalaraTransactionLineTransfer::DETAILS => '[{"id":0,"transactionLineId":0,"transactionId":0,"country":"US","region":"MI","exemptAmount":0,"jurisCode":"26","jurisName":"MICHIGAN","stateAssignedNo":"","jurisType":"STA","jurisdictionType":"State","nonTaxableAmount":0,"rate":0.06,"tax":18.83,"taxableAmount":313.82,"taxType":"Sales","taxSubTypeId":"S","taxName":"MI STATE TAX","taxAuthorityTypeId":45,"taxCalculated":18.83,"rateType":"General","rateTypeCode":"G","unitOfBasis":"PerCurrencyUnit","isNonPassThru":false,"isFee":false,"reportingTaxableUnits":313.82,"reportingNonTaxableUnits":0,"reportingExemptUnits":0,"reportingTax":18.83,"reportingTaxCalculated":18.83,"liabilityType":"Seller"}]',
        ])->build();
    }
}
