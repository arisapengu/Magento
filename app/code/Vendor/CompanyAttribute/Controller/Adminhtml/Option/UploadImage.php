<?php
namespace Vendor\CompanyAttribute\Controller\Adminhtml\Option;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

class UploadImage extends Action
{
    const ADMIN_RESOURCE = 'Magento_Catalog::attributes_attributes';
    const IMAGE_DIR      = 'vendor/attribute_option';

    private JsonFactory     $jsonFactory;
    private UploaderFactory $uploaderFactory;
    private Filesystem      $filesystem;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->jsonFactory     = $jsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem      = $filesystem;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDir  = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $uploadDir = $mediaDir->getAbsolutePath(self::IMAGE_DIR);
            $res       = $uploader->save($uploadDir);

            return $result->setData([
                'success' => true,
                'path'    => self::IMAGE_DIR . '/' . $res['file'],
                'url'     => $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
                             . self::IMAGE_DIR . '/' . $res['file'],
            ]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
