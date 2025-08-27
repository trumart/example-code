<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\ExportService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Xml Файл для в 1С EnterpriseData
     *
     * @return ResponseFactory|Application|Response|\Illuminate\Foundation\Application
     */
    public function xmlEnterpriseData(): ResponseFactory|Application|Response|\Illuminate\Foundation\Application
    {

        $inp = $this->request->validate([
            'id' => ['required', 'numeric'],
        ]);

        // Xml для EnterpriseData
        $xml = (new ExportService())->financeOperationExportXmlForEnterpriseData($inp['id']);

        // Возвращаем корректный Response
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            // ->header('Content-Disposition', 'attachment; filename="' . $filename . '"') // Раскомментировать если нужен download
            ->header('Cache-Control', 'no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');

    }

    /**
     * Выгрузка на ftp Xml Файла для в 1С EnterpriseData
     *
     * @return JsonResponse
     */
    public function xmlEnterpriseDataUnload(): JsonResponse
    {

        $inp = $this->request->validate([
            'id' => ['required', 'numeric'],
        ]);

        return (new ExportService())->financeOperationExportXmlForEnterpriseDataToFtp($inp['id']);

    }
}
