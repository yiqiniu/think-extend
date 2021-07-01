<?php


namespace yiqiniu\extend\library;


use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Exception;


class ExportFile
{


    /**
     * 导出PDF
     * @param string $filename 文件名
     * @param string $html_content 文件内容
     * @param array  $chinaFont 中文字体
     * @throws \Mpdf\MpdfException
     *
     *
     */
    protected const  PDF_EXT = '.pdf';

    public function exportPdf(string $filename, string $html_content, array $chinaFont = [])
    {

        if (!class_exists(Mpdf::class)) {
            throw  new  Exception("请先执行 'composer require mpdf/mpdf'");
        }


        $pdf_params = [];
        if (!empty($chinaFont) && !empty($chinaFont['name']) && !empty($chinaFont['path']) && !empty($chinaFont['data'])) {

            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];
            $pdf_params = [
                'fontDir' => array_merge($fontDirs, [
                    $chinaFont['path'],
                ]),
                'fontdata' => $fontData + $chinaFont['data'],
                'default_font' => $chinaFont['name']
            ];
        }


        $mpdf = new Mpdf($pdf_params);
        // $mpdf->AddPage('L');
        $mpdf->WriteHTML($html_content);

        ob_start();
        if (request()->isGet()) {
            $mpdf->Output($filename . self::PDF_EXT, 'i');

        } else {
            $data = $mpdf->Output($filename . self::PDF_EXT, 's');
            api_result(API_SUCCESS, '', $data);
        }

        exit;


    }

    /**
     * 导出excel
     * @param string $filename 文件名
     * @param array  $titles excel 列标题
     * @param array  $datalist excel 内容
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportExcel(string $filename, array $titles, array $datalist)
    {
        if (!class_exists(Spreadsheet::class)) {
            throw  new  Exception("请先执行 'composer require phpoffice/phpspreadsheet'");
        }
        try {
            $count = count($titles);  //计算表头数量

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $keys = array_keys($titles);
            //$col=65;

            for ($i = 65; $i < $count + 65; $i++) {     //数字转字母从65开始，循环设置表头：
                $sheet->setCellValue(strtoupper(chr($i)) . '1', $titles[$keys[$i - 65]]);

            }

            /*--------------开始从数据库提取信息插入Excel表中------------------*/


            foreach ($datalist as $key => $item) {             //循环设置单元格：
                //$key+2,因为第一行是表头，所以写到表格时   从第二行开始写

                for ($i = 65; $i < $count + 65; $i++) {     //数字转字母从65开始：
                    $sheet->setCellValue(strtoupper(chr($i)) . ($key + 2), $item[$keys[$i - 65]]);
                    $spreadsheet->getActiveSheet()->getColumnDimension(strtoupper(chr($i)))->setWidth(20); //固定列宽
                    //$sheet->getStyle(strtoupper(chr($i)))->getAlignment()->setWrapText(true);
                }

            }

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');

            //删除清空：
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Exception $e) {
            throw $e;
        }

    }

}