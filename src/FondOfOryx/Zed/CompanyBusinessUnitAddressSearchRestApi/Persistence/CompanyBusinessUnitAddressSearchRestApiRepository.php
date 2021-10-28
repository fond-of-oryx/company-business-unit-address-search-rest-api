<?php

namespace FondOfOryx\Zed\CompanyBusinessUnitAddressSearchRestApi\Persistence;

use ArrayObject;
use Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer;
use Orm\Zed\CompanyBusinessUnit\Persistence\Map\SpyCompanyBusinessUnitTableMap;
use Orm\Zed\CompanyUnitAddress\Persistence\Map\SpyCompanyUnitAddressTableMap;
use Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \FondOfOryx\Zed\CompanyBusinessUnitAddressSearchRestApi\Persistence\CompanyBusinessUnitAddressSearchRestApiPersistenceFactory getFactory()
 */
class CompanyBusinessUnitAddressSearchRestApiRepository extends AbstractRepository implements CompanyBusinessUnitAddressSearchRestApiRepositoryInterface
{
    public const KEY_DEFAULT_SHIPPING_IDS = 'defaultShippingAddressIds';
    public const KEY_DEFAULT_BILLING_IDS = 'defaultBillingAddressIds';

    /**
     * @var array
     */
    protected $defaultBillingAndShippingIds = [];

    /**
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer
     */
    public function searchCompanyBusinessUnitAddress(
        CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
    ): CompanyBusinessUnitAddressListTransfer {
        $this->prepareDefaultBillingAndShippingIds($companyBusinessUnitAddressListTransfer);

        $companyUnitAddressQuery = $this->getBaseQuery();
        $companyUnitAddressQuery = $this->addCompanyQuery($companyUnitAddressQuery, $companyBusinessUnitAddressListTransfer);
        $companyUnitAddressQuery = $this->addAddressFilter($companyUnitAddressQuery, $companyBusinessUnitAddressListTransfer);

        $companyUnitAddressQuery = $this->addSort($companyUnitAddressQuery, $companyBusinessUnitAddressListTransfer);
        $companyUnitAddressQuery = $this->preparePagination($companyUnitAddressQuery, $companyBusinessUnitAddressListTransfer);

        $companyBusinessUnitAddresses = $this->getFactory()
            ->createCompanyBusinessUnitAddressMapper()
            ->mapEntityCollectionToTransfers($companyUnitAddressQuery->find(), $this->defaultBillingAndShippingIds);

        $this->defaultBillingAndShippingIds = [];

        return $companyBusinessUnitAddressListTransfer->setCompanyBusinessUnitAddresses(new ArrayObject($companyBusinessUnitAddresses));
    }

    /**
     * @return \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery
     */
    protected function getBaseQuery(): SpyCompanyUnitAddressQuery
    {
        return $this->getFactory()
            ->getCompanyUnitAddressQuery()
            ->clear();
    }

    /**
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return array
     */
    protected function getDefaultAddressIds(CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer): array
    {
        $query = $this->getFactory()
            ->getCompanyBusinessUnitQuery()
            ->clear()
            ->useCompanyUserQuery()
            ->filterByIsActive(true)
            ->filterByFkCustomer($companyBusinessUnitAddressListTransfer->getCustomerId())
            ->endUse()
            ->add(
                SpyCompanyBusinessUnitTableMap::COL_DEFAULT_SHIPPING_ADDRESS,
                null,
                Criteria::ISNOTNULL
            )
            ->addOr(
                SpyCompanyBusinessUnitTableMap::COL_DEFAULT_BILLING_ADDRESS,
                null,
                Criteria::ISNOTNULL
            );

        return $query->select([SpyCompanyBusinessUnitTableMap::COL_DEFAULT_SHIPPING_ADDRESS, SpyCompanyBusinessUnitTableMap::COL_DEFAULT_BILLING_ADDRESS])->find()->getData();
    }

    /**
     * @param \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery $companyUnitAddressQuery
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery
     */
    protected function addCompanyQuery(
        SpyCompanyUnitAddressQuery $companyUnitAddressQuery,
        CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
    ): SpyCompanyUnitAddressQuery {
        if ($companyBusinessUnitAddressListTransfer->getCompanyUuid() !== null && $companyBusinessUnitAddressListTransfer->getCompanyBusinessUnitUuid() !== null) {
            return $companyUnitAddressQuery
                ->useCompanyQuery()
                    ->useCompanyUserQuery()
                        ->filterByFkCustomer($companyBusinessUnitAddressListTransfer->getCustomerId())
                        ->filterByIsActive(true)
                    ->endUse()
                    ->filterByUuid($companyBusinessUnitAddressListTransfer->getCompanyUuid())
                    ->filterByIsActive(true)
                    ->useCompanyBusinessUnitQuery()
                        ->filterByUuid($companyBusinessUnitAddressListTransfer->getCompanyBusinessUnitUuid())
                    ->endUse()
                ->endUse();
        }

        if ($companyBusinessUnitAddressListTransfer->getCompanyBusinessUnitUuid() !== null) {
            return $companyUnitAddressQuery
                ->useCompanyQuery()
                    ->useCompanyUserQuery()
                        ->filterByFkCustomer($companyBusinessUnitAddressListTransfer->getCustomerId())
                        ->filterByIsActive(true)
                    ->endUse()
                    ->filterByIsActive(true)
                    ->useCompanyBusinessUnitQuery()
                        ->filterByUuid($companyBusinessUnitAddressListTransfer->getCompanyBusinessUnitUuid())
                    ->endUse()
                ->endUse();
        }

        if ($companyBusinessUnitAddressListTransfer->getCompanyUuid() !== null) {
            return $companyUnitAddressQuery
                ->useCompanyQuery()
                    ->useCompanyUserQuery()
                        ->filterByFkCustomer($companyBusinessUnitAddressListTransfer->getCustomerId())
                        ->filterByIsActive(true)
                    ->endUse()
                        ->filterByUuid($companyBusinessUnitAddressListTransfer->getCompanyUuid())
                        ->filterByIsActive(true)
                ->endUse();
        }

        return $companyUnitAddressQuery
            ->useCompanyQuery()
                ->useCompanyUserQuery()
                    ->filterByFkCustomer($companyBusinessUnitAddressListTransfer->getCustomerId())
                    ->filterByIsActive(true)
                    ->leftJoinCustomer()
                ->endUse()
                ->filterByIsActive(true)
            ->endUse();
    }

    /**
     * @param \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery $companyUnitAddressQuery
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery
     */
    protected function addSort(
        SpyCompanyUnitAddressQuery $companyUnitAddressQuery,
        CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
    ): SpyCompanyUnitAddressQuery {
        $sort = $companyBusinessUnitAddressListTransfer->getSort();

        if ($sort === null) {
            return $companyUnitAddressQuery;
        }

        $tableMap = SpyCompanyUnitAddressTableMap::getTableMap();
        $sortFields = $this->getFactory()->getConfig()->getSortFields();

        [$sortField, $direction] = explode(' ', preg_replace('/(([a-z]+)(_[a-z]+)*)_(asc|desc)/', '$1 $4', $sort));

        if (!in_array($sortField, $sortFields, true) || !$tableMap->hasColumn($sortField)) {
            return $companyUnitAddressQuery;
        }

        $columnMap = $tableMap->getColumn($sortField);

        $companyUnitAddressQuery->orderBy(
            $columnMap->getFullyQualifiedName(),
            $direction === 'desc' ? Criteria::DESC : Criteria::ASC
        );

        return $companyUnitAddressQuery;
    }

    /**
     * @param \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery $companyUnitAddressQuery
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    protected function preparePagination(
        SpyCompanyUnitAddressQuery $companyUnitAddressQuery,
        CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
    ): ModelCriteria {
        $config = $this->getFactory()->getConfig();
        $itemsPerPage = $config->getItemsPerPage();
        $validItemsPerPageOptions = $config->getValidItemsPerPageOptions();
        $paginationTransfer = $companyBusinessUnitAddressListTransfer->requirePagination()->getPagination();
        $page = $paginationTransfer->getPage() ?? 1;
        $maxPerPage = $paginationTransfer->getMaxPerPage();

        if ($maxPerPage === null || !in_array($maxPerPage, $validItemsPerPageOptions, true)) {
            $maxPerPage = $itemsPerPage;
        }

        $propelModelPager = $companyUnitAddressQuery->paginate($page, $maxPerPage);

        $paginationTransfer->setNbResults($propelModelPager->getNbResults())
            ->setFirstIndex($propelModelPager->getFirstIndex())
            ->setLastIndex($propelModelPager->getLastIndex())
            ->setFirstPage($propelModelPager->getFirstPage())
            ->setLastPage($propelModelPager->getLastPage())
            ->setNextPage($propelModelPager->getNextPage())
            ->setPreviousPage($propelModelPager->getPreviousPage());

        return $propelModelPager->getQuery();
    }

    /**
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return void
     */
    protected function prepareDefaultBillingAndShippingIds(CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer): void
    {
        $ids = $this->getDefaultAddressIds($companyBusinessUnitAddressListTransfer);

        $billingAddressIds = [];
        $shippingAddressIds = [];
        foreach ($ids as $idPair) {
            if (array_key_exists(SpyCompanyBusinessUnitTableMap::COL_DEFAULT_BILLING_ADDRESS, $idPair) && $idPair[SpyCompanyBusinessUnitTableMap::COL_DEFAULT_BILLING_ADDRESS] !== null) {
                $billingAddressIds[] = $idPair[SpyCompanyBusinessUnitTableMap::COL_DEFAULT_BILLING_ADDRESS];
            }

            if (array_key_exists(SpyCompanyBusinessUnitTableMap::COL_DEFAULT_SHIPPING_ADDRESS, $idPair) && $idPair[SpyCompanyBusinessUnitTableMap::COL_DEFAULT_SHIPPING_ADDRESS] !== null) {
                $shippingAddressIds[] = $idPair[SpyCompanyBusinessUnitTableMap::COL_DEFAULT_SHIPPING_ADDRESS];
            }
        }

        $this->defaultBillingAndShippingIds = [
            static::KEY_DEFAULT_SHIPPING_IDS => $shippingAddressIds,
            static::KEY_DEFAULT_BILLING_IDS => $billingAddressIds,
        ];
    }

    /**
     * @param \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery $companyUnitAddressQuery
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return \Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery
     */
    protected function addAddressFilter(
        SpyCompanyUnitAddressQuery $companyUnitAddressQuery,
        CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
    ): SpyCompanyUnitAddressQuery {
        if ($companyBusinessUnitAddressListTransfer->getDefaultShipping() === false && $companyBusinessUnitAddressListTransfer->getDefaultBilling() === false) {
            return $companyUnitAddressQuery;
        }

        return $companyUnitAddressQuery
            ->useSpyCompanyUnitAddressToCompanyBusinessUnitQuery()
            ->filterByFkCompanyUnitAddress_In($this->getAddressIds($companyBusinessUnitAddressListTransfer))
            ->endUse();
    }

    /**
     * @param \Generated\Shared\Transfer\CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer
     *
     * @return int[]
     */
    protected function getAddressIds(CompanyBusinessUnitAddressListTransfer $companyBusinessUnitAddressListTransfer): array
    {
        if (
            $companyBusinessUnitAddressListTransfer->getDefaultShipping() === true
            && $companyBusinessUnitAddressListTransfer->getDefaultBilling() === true
            && array_key_exists(static::KEY_DEFAULT_SHIPPING_IDS, $this->defaultBillingAndShippingIds)
            && array_key_exists(static::KEY_DEFAULT_BILLING_IDS, $this->defaultBillingAndShippingIds)
        ) {
            return array_merge($this->defaultBillingAndShippingIds[static::KEY_DEFAULT_SHIPPING_IDS], $this->defaultBillingAndShippingIds[static::KEY_DEFAULT_BILLING_IDS]);
        }

        if (
            $companyBusinessUnitAddressListTransfer->getDefaultShipping() === true
            && array_key_exists(static::KEY_DEFAULT_SHIPPING_IDS, $this->defaultBillingAndShippingIds)
        ) {
            return $this->defaultBillingAndShippingIds[static::KEY_DEFAULT_SHIPPING_IDS];
        }
        if (
            $companyBusinessUnitAddressListTransfer->getDefaultBilling() === true
            && array_key_exists(static::KEY_DEFAULT_BILLING_IDS, $this->defaultBillingAndShippingIds)
        ) {
            return $this->defaultBillingAndShippingIds[static::KEY_DEFAULT_BILLING_IDS];
        }

        return [];
    }
}
