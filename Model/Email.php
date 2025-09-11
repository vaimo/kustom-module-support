<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Support\Model;

use Klarna\Base\Helper\Debug\DebugDataObject;
use Klarna\Support\Mail\Template\TransportBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\Io\File;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 *
 * @internal
 */
class Email
{
    public const TEMPLATE_NAME = 'klarna_support_email_template';

    public const KLARNA_SUPPORT_MAIL = 'magento@klarna.com';

    public const DEBUG_CONTACT_NAME_PATTERN = '_test_';

    public const NOT_SENDING_MAIL_NAME_PATTERN = '_not_sending_mail_';

    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var ModuleListInterface
     */
    private ModuleListInterface $moduleList;
    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $productMetadata;
    /**
     * @var DriverInterface
     */
    private DriverInterface $driverInterface;
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;
    /**
     * @var File
     */
    private file $file;
    /**
     * @var Escaper
     */
    private Escaper $escaper;
    /**
     * @var DebugDataObject
     */
    private DebugDataObject $dataObject;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     * @param ProductMetadataInterface $productMetadata
     * @param DriverInterface $driverInterface
     * @param Filesystem $filesystem
     * @param File $file
     * @param Escaper $escaper
     * @param DebugDataObject $dataObject
     * @param ManagerInterface $eventManager
     * @codeCoverageIgnore
     */
    public function __construct(
        TransportBuilder         $transportBuilder,
        StoreManagerInterface    $storeManager,
        ModuleListInterface      $moduleList,
        ProductMetadataInterface $productMetadata,
        DriverInterface          $driverInterface,
        Filesystem               $filesystem,
        File                     $file,
        Escaper                  $escaper,
        DebugDataObject          $dataObject,
        ManagerInterface         $eventManager
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager     = $storeManager;
        $this->moduleList       = $moduleList;
        $this->productMetadata  = $productMetadata;
        $this->driverInterface  = $driverInterface;
        $this->filesystem       = $filesystem;
        $this->file             = $file;
        $this->escaper          = $escaper;
        $this->dataObject       = $dataObject;
        $this->eventManager     = $eventManager;
    }

    /**
     * Getting the email content
     *
     * @param array $formData
     * @return array
     */
    public function getTemplateContent(array $formData): array
    {
        $formData = $this->stripCode($formData);

        $formData['module_versions'] = $this->getModuleVersions();
        $formData['php_version'] = phpversion();
        $formData['products'] = implode("<br/>", $formData['data']);
        $formData['shop_version'] = $this->productMetadata->getVersion() . ' ' . $this->productMetadata->getEdition();

        return $formData;
    }

    /**
     * Stripping code from the input
     *
     * @param array $data
     * @return array
     */
    private function stripCode(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->stripCode($value);
                continue;
            }

            $data[$key] = $this->escaper->escapeHtml($value);
        }

        return $data;
    }

    /**
     * Sending the email
     *
     * @param array $formData
     * @throws LocalizedException
     */
    public function send(array $formData): void
    {
        $sender = [
            'name'  => $formData['contact_name'],
            'email' => $formData['contact_email']
        ];
        $addTo = self::KLARNA_SUPPORT_MAIL;

        if (strpos($formData['contact_name'], self::DEBUG_CONTACT_NAME_PATTERN) !== false) {
            $addTo = $formData['contact_email'];
        }

        $this->transportBuilder->setTemplateIdentifier(self::TEMPLATE_NAME);
        $this->transportBuilder->setFromByScope($sender);
        $this->transportBuilder->addTo($addTo, 'Klarna support');
        $this->transportBuilder->setTemplateVars($formData);
        $this->transportBuilder->setTemplateOptions(
            [
                'area' => 'adminhtml',
                'store' => $this->storeManager->getStore()->getId()
            ]
        );

        $this->attachTheAttachments($formData['attachment'] ?? []);
        $this->addAttachmentFromData($this->getDebugData($formData));

        if (strpos($formData['contact_name'], self::NOT_SENDING_MAIL_NAME_PATTERN) === false) {
                $this->transportBuilder->getTransport()->sendMessage();
        }
    }

    /**
     * Attach the attachments sent from support to the email
     *
     * @param array $attachments
     * @return void
     * @throws FileSystemException
     */
    private function attachTheAttachments(array $attachments): void
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $path = $directory->getAbsolutePath('/klarna');

        foreach ($attachments as $attachment) {
            $file = $this->file->getPathInfo($attachment['file'])['basename'];
            $this->transportBuilder->addAttachment(
                $this->driverInterface->fileGetContents(
                    "{$path}/{$file}"
                ),
                $attachment['name']
            );
        }
    }

    /**
     * Adds attachment(s) from given data to the email
     *
     * @param string[] $data
     * @return void
     */
    private function addAttachmentFromData(array $data): void
    {
        foreach ($data as $key => $stringData) {
            $this->transportBuilder->addAttachment(
                $stringData,
                $key . '.json'
            );
        }
    }

    /**
     * Checks if the field with given key is selected
     *
     * @param array $array
     * @param string $key
     * @return bool
     */
    private function isSelected(array $array, string $key): bool
    {
        return isset($array[$key]) && $array[$key] === "1";
    }

    /**
     * Returns the modules to ignore
     *
     * @param array $formData
     * @return array
     */
    public function getModulesToIgnore(array $formData): array
    {
        $selectedKlarnaProducts = $formData['data'];
        $allPossibleKlarnaProducts = [
            "KP",
            "OM",
            "OSM",
            "GraphQL",
            "KCO",
            "KSA",
            'SIWK',
            'KEC'
        ];

        $modulesToIgnore = array_map(function ($product) {
            return strtolower("klarna_" . $product);
        }, array_diff($allPossibleKlarnaProducts, $selectedKlarnaProducts));

        if (!$this->isSelected($formData, 'include_klarna_settings')) {
            $modulesToIgnore[] = 'klarna_configs';
        }

        if (!$this->isSelected($formData, 'include_tax_settings')) {
            $modulesToIgnore[] = 'klarna_tax_configs';
        }

        return $modulesToIgnore;
    }

    /**
     * Retrieves the debug data
     *
     * @param array $formData
     * @return array
     */
    public function getDebugData(array $formData): array
    {
        $modulesToIgnore = $this->getModulesToIgnore($formData);
        $this->dataObject->ignoreModules($modulesToIgnore);
        $this->eventManager->dispatch('klarna_debug_data_collector', [
            'debug_data_object' => $this->dataObject
        ]);

        return $this->dataObject->getData();
    }

    /**
     * Getting the module versions
     *
     * @return string
     */
    private function getModuleVersions(): string
    {
        $allModules = $this->moduleList->getAll();
        $klarnaModuleNames = array_filter($allModules, function ($key) {
            return strpos($key, 'Klarna_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $klarnaVersions = '';
        ksort($klarnaModuleNames);
        foreach ($klarnaModuleNames as $name => $moduleDetails) {
            $klarnaVersions .= $name . ': ' . $moduleDetails['setup_version'] . "<br/>";
        }

        return $klarnaVersions;
    }
}
