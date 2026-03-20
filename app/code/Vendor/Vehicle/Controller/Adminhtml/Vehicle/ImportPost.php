<?php
declare(strict_types=1);

namespace Vendor\Vehicle\Controller\Adminhtml\Vehicle;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Vendor\Vehicle\Model\ResourceModel\Vehicle as VehicleResource;

class ImportPost extends Action
{
    const ADMIN_RESOURCE = 'Vendor_Vehicle::vehicle_manage';

    public function __construct(
        Context $context,
        private readonly VehicleResource $vehicleResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $file = $this->getRequest()->getFiles('csv_file');

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->messageManager->addErrorMessage(__('Please upload a valid CSV file.'));
            return $redirect->setPath('*/*/index');
        }

        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
            $this->messageManager->addErrorMessage(__('File must be a .csv'));
            return $redirect->setPath('*/*/index');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->messageManager->addErrorMessage(__('Could not read uploaded file.'));
            return $redirect->setPath('*/*/index');
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->messageManager->addErrorMessage(__('CSV file is empty.'));
            return $redirect->setPath('*/*/index');
        }

        $header = array_map('trim', array_map('strtolower', $header));
        $required = ['make', 'model', 'year_start'];
        $missing = array_diff($required, $header);

        if (!empty($missing)) {
            fclose($handle);
            $this->messageManager->addErrorMessage(
                __('CSV missing required columns: %1', implode(', ', $missing))
            );
            return $redirect->setPath('*/*/index');
        }

        $rows       = [];
        $errors     = [];
        $lineNumber = 1;
        $now        = date('Y-m-d H:i:s');

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $data = array_combine($header, array_pad($row, count($header), null));

            // Validate required fields
            if (empty(trim((string)($data['make'] ?? ''))) ||
                empty(trim((string)($data['model'] ?? ''))) ||
                empty(trim((string)($data['year_start'] ?? '')))) {
                $errors[] = __('Line %1: make, model, year_start are required.', $lineNumber);
                continue;
            }

            $yearStart = (int)$data['year_start'];
            if ($yearStart < 1900 || $yearStart > 2100) {
                $errors[] = __('Line %1: invalid year_start "%2".', $lineNumber, $data['year_start']);
                continue;
            }

            $yearEnd = isset($data['year_end']) && trim((string)$data['year_end']) !== ''
                ? (int)$data['year_end']
                : null;

            // Backward-compatible: old CSVs may have 'submodel'/'engine' columns
            $modelCodeRaw  = $data['model_code'] ?? $data['submodel'] ?? '';
            $engineCodeRaw = $data['engine_code'] ?? $data['engine'] ?? '';
            $modelGenRaw   = $data['model_gen'] ?? '';

            $rows[] = [
                'make'        => trim((string)$data['make']),
                'model'       => trim((string)$data['model']),
                'year_start'  => $yearStart,
                'year_end'    => $yearEnd,
                'model_code'  => trim((string)$modelCodeRaw) !== '' ? trim((string)$modelCodeRaw) : null,
                'model_gen'   => trim((string)$modelGenRaw) !== '' ? trim((string)$modelGenRaw) : null,
                'engine_code' => trim((string)$engineCodeRaw) !== '' ? trim((string)$engineCodeRaw) : null,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        fclose($handle);

        $inserted = 0;
        if (!empty($rows)) {
            try {
                $inserted = $this->vehicleResource->bulkInsert($rows);
                $this->messageManager->addSuccessMessage(
                    __('Successfully imported %1 vehicle(s).', $inserted)
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Import failed: %1', $e->getMessage())
                );
            }
        }

        foreach ($errors as $error) {
            $this->messageManager->addWarningMessage($error);
        }

        if ($inserted === 0 && empty($errors)) {
            $this->messageManager->addWarningMessage(__('No valid rows found in CSV.'));
        }

        return $redirect->setPath('*/*/index');
    }
}
