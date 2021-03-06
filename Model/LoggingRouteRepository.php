<?php


namespace FireGento\WebapiMetrics\Model;

use FireGento\WebapiMetrics\Api\Data\LoggingEntryInterface;
use FireGento\WebapiMetrics\Api\Data\LoggingRouteInterface;
use FireGento\WebapiMetrics\Api\Data\LoggingRouteInterfaceFactory;
use FireGento\WebapiMetrics\Api\Data\LoggingRouteSearchResultsInterfaceFactory;
use FireGento\WebapiMetrics\Api\LoggingRouteRepositoryInterface;
use FireGento\WebapiMetrics\Model\ResourceModel\LoggingRoute as ResourceLoggingRoute;
use FireGento\WebapiMetrics\Model\ResourceModel\LoggingRoute\CollectionFactory as LoggingRouteCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class LoggingRouteRepository implements LoggingRouteRepositoryInterface
{

    protected $resource;

    protected $loggingRouteFactory;

    protected $loggingRouteCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataLoggingRouteFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceLoggingRoute $resource
     * @param LoggingRouteFactory $loggingRouteFactory
     * @param LoggingRouteInterfaceFactory $dataLoggingRouteFactory
     * @param LoggingRouteCollectionFactory $loggingRouteCollectionFactory
     * @param LoggingRouteSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceLoggingRoute $resource,
        LoggingRouteFactory $loggingRouteFactory,
        LoggingRouteInterfaceFactory $dataLoggingRouteFactory,
        LoggingRouteCollectionFactory $loggingRouteCollectionFactory,
        LoggingRouteSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->loggingRouteFactory = $loggingRouteFactory;
        $this->loggingRouteCollectionFactory = $loggingRouteCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataLoggingRouteFactory = $dataLoggingRouteFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(LoggingRouteInterface $loggingRoute) {
        $loggingRouteData = $this->extensibleDataObjectConverter->toNestedArray(
            $loggingRoute,
            [],
            LoggingRouteInterface::class
        );

        /** @var LoggingRoute $loggingRouteModel */
        $loggingRouteModel = $this->loggingRouteFactory->create()->setData($loggingRouteData);
        $this->resource->load(
            $loggingRouteModel,
            $loggingRouteModel->getData(LoggingRouteInterface::KEY_ROUTE_NAME),
            LoggingRouteInterface::KEY_ROUTE_NAME
        );

        //if no entity_id after load then we need set data changes and save it
        if(!$loggingRouteModel->getEntityId()) {
            $loggingRouteModel->setDataChanges(true);
        }
        
        try {
            $this->resource->save($loggingRouteModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the loggingRoute: %1',
                $exception->getMessage()
            ));
        }
        return $loggingRouteModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($loggingRouteId)
    {
        $loggingRoute = $this->loggingRouteFactory->create();
        $this->resource->load($loggingRoute, $loggingRouteId);
        if (!$loggingRoute->getId()) {
            throw new NoSuchEntityException(__('LoggingRoute with id "%1" does not exist.', $loggingRouteId));
        }
        return $loggingRoute->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->loggingRouteCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            LoggingRouteInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        LoggingRouteInterface $loggingRoute
    ) {
        try {
            $loggingRouteModel = $this->loggingRouteFactory->create();
            $this->resource->load($loggingRouteModel, $loggingRoute->getEntityId());
            $this->resource->delete($loggingRouteModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the LoggingRoute: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($loggingRouteId)
    {
        return $this->delete($this->get($loggingRouteId));
    }
}
