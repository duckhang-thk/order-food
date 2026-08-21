<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ExcelOrderService
{
    /**
     * Đọc file Excel và trả về danh sách order.
     *
     * @param string $filePath Đường dẫn file Excel tạm thời
     * @return array<int, array<string, mixed>>
     */
    public function read(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('Excel file not found.');
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        // Chỉ lấy sheet đầu tiên
        $worksheet = $spreadsheet->getSheet(0);

        $rows = $worksheet->toArray(
            null,
            true,
            true,
            true
        );

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($rows)) {
            throw new RuntimeException('Excel file is empty.');
        }

        return $this->parseRows($rows);
    }

    /**
     * Parse Excel rows.
     *
     * @param array<int|string, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function parseRows(array $rows): array
    {
        $orderRowIndex = null;

        foreach ($rows as $index => $row) {
            if (isset($row['A']) && trim((string)$row['A']) === 'ORDER') {
                $orderRowIndex = $index;
                break;
            }
        }

        if ($orderRowIndex === null) {
            throw new RuntimeException('ORDER section not found.');
        }

        $headerRowIndex = $orderRowIndex + 1;

        $expectedHeaders = [
            'A' => 'NO',
            'B' => 'EMPLOYEE NAME',
            'C' => 'ORDER',
            'D' => 'TEAM',
            'E' => 'NOTES',
        ];

        foreach ($expectedHeaders as $column => $expected) {
            $actual = strtoupper(trim((string)($rows[$headerRowIndex][$column] ?? '')));

            if ($actual !== $expected) {
                throw new RuntimeException(
                    'Excel format is invalid. Expected columns: ' .
                        implode(', ', array_values($expectedHeaders))
                );
            }
        }

        $orders = [];

        foreach ($rows as $index => $row) {
            // Bỏ dòng ORDER và header
            if ($index <= $headerRowIndex) {
                continue;
            }

            // Nếu NO không có thì coi như hết dữ liệu
            if (empty($row['A'])) {
                continue;
            }

            $orders[] = [
                'no' => (int)$row['A'],
                'employee_name' => trim((string)($row['B'] ?? '')),
                'order' => trim((string)($row['C'] ?? '')),
                'team' => trim((string)($row['D'] ?? '')),
                'notes' => trim((string)($row['E'] ?? '')),
            ];
        }

        return $orders;
    }

    /**
     * Kiểm tra row có hoàn toàn rỗng hay không.
     *
     * @param array<int, mixed> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }

    public function groupOrdersByTeam(array $orders): array
    {
        $result = [];

        foreach ($orders as $order) {
            // Không tính người không tham gia
            if ($order['order'] === 'Không tham gia/ランチ会参加不可') {
                continue;
            }

            $team = $order['team'];
            $menu = $order['order'];

            if (!isset($result[$team])) {
                $result[$team] = [];
            }

            if (!isset($result[$team][$menu])) {
                $result[$team][$menu] = 0;
            }

            $result[$team][$menu]++;
        }

        return $result;
    }

    public function formatForChatwork(array $groupedOrders): string
    {
        $message = "[info][title]Lunch Order[/title]\n";

        foreach ($groupedOrders as $team => $orders) {
            $message .= "\n[hr]\n";
            $message .= $team . "\n";

            foreach ($orders as $menu => $count) {
                $message .= "・{$menu}: {$count} phần\n";
            }
        }

        $message .= "[/info]";

        return $message;
    }
}
