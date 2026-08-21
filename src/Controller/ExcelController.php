<?php

namespace App\Controller;

use App\Service\ExcelOrderService;
use App\Service\ChatworkService;
use Cake\Http\Exception\BadRequestException;

class ExcelController extends AppController
{
    public function upload()
    {
        if ($this->request->is('get')) {
            return;
        }

        $file = $this->request->getData('excel_file');
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestException('Excel upload failed.');
        }

        $tmpPath = $file->getStream()->getMetadata('uri');

        $service = new ExcelOrderService();

        $orders = $service->read($tmpPath);
        $grouped = $service->groupOrdersByTeam($orders);
        $message = $service->formatForChatwork($grouped);
        $chatworkService = new ChatworkService();

        $chatworkService->send($message);
        debug($grouped);

        debug($message);
        debug($orders);
        die();
    }

    public function testCookie()
    {
        debug($_SERVER['HTTP_COOKIE'] ?? null);
        debug($_COOKIE);
        debug($this->request->getHeaderLine('Cookie'));
        exit;
    }
}
