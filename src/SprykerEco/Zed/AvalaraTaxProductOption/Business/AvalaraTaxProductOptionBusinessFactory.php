<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerEco\Zed\AvalaraTaxProductOption\AvalaraTaxProductOptionDependencyProvider;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator\MultiAddressShipmentProductOptionAvalaraTaxCalculator;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator\ProductOptionAvalaraTaxCalculatorInterface;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator\SingleAddressShipmentProductOptionAvalaraTaxCalculator;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Expander\AvalaraCreateTransactionRequestExpander;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Expander\AvalaraCreateTransactionRequestExpanderInterface;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapper;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapperInterface;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\StrategyResolver\ProductOptionTaxCalculatorStrategyResolver;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\StrategyResolver\ProductOptionTaxCalculatorStrategyResolverInterface;
use SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface;
use SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Service\AvalaraTaxProductOptionToUtilEncodingServiceInterface;

/**
 * @method \SprykerEco\Zed\AvalaraTaxProductOption\AvalaraTaxProductOptionConfig getConfig()
 */
class AvalaraTaxProductOptionBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Business\Expander\AvalaraCreateTransactionRequestExpanderInterface
     */
    public function createAvalaraCreateTransactionRequestExpander(): AvalaraCreateTransactionRequestExpanderInterface
    {
        return new AvalaraCreateTransactionRequestExpander($this->createAvalaraLineItemMapper());
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator\ProductOptionAvalaraTaxCalculatorInterface
     */
    protected function createSingleAddressShipmentProductOptionAvalaraTaxCalculator(): ProductOptionAvalaraTaxCalculatorInterface
    {
        return new SingleAddressShipmentProductOptionAvalaraTaxCalculator(
            $this->getMoneyFacade(),
            $this->getUtilEncodingService()
        );
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator\ProductOptionAvalaraTaxCalculatorInterface
     */
    protected function createMultiAddressShipmentProductOptionAvalaraTaxCalculator(): ProductOptionAvalaraTaxCalculatorInterface
    {
        return new MultiAddressShipmentProductOptionAvalaraTaxCalculator(
            $this->getMoneyFacade(),
            $this->getUtilEncodingService()
        );
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapperInterface
     */
    public function createAvalaraLineItemMapper(): AvalaraLineItemMapperInterface
    {
        return new AvalaraLineItemMapper($this->getMoneyFacade());
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface
     */
    public function getMoneyFacade(): AvalaraTaxProductOptionToMoneyFacadeInterface
    {
        return $this->getProvidedDependency(AvalaraTaxProductOptionDependencyProvider::FACADE_MONEY);
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Service\AvalaraTaxProductOptionToUtilEncodingServiceInterface
     */
    public function getUtilEncodingService(): AvalaraTaxProductOptionToUtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(AvalaraTaxProductOptionDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    /**
     * @deprecated Exists for Backward Compatibility reasons only. Use {@link createMultiAddressShipmentProductOptionAvalaraTaxCalculator()} instead.
     *
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Business\StrategyResolver\ProductOptionTaxCalculatorStrategyResolverInterface
     */
    public function createProductItemTaxRateCalculatorStrategyResolver(): ProductOptionTaxCalculatorStrategyResolverInterface
    {
        $strategyContainer = [];

        $strategyContainer[ProductOptionTaxCalculatorStrategyResolver::STRATEGY_KEY_WITHOUT_MULTI_SHIPMENT] = function () {
            return $this->createSingleAddressShipmentProductOptionAvalaraTaxCalculator();
        };

        $strategyContainer[ProductOptionTaxCalculatorStrategyResolver::STRATEGY_KEY_WITH_MULTI_SHIPMENT] = function () {
            return $this->createMultiAddressShipmentProductOptionAvalaraTaxCalculator();
        };

        return new ProductOptionTaxCalculatorStrategyResolver($strategyContainer);
    }
}
