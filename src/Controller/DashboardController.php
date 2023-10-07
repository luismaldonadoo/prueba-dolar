<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer as Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends AbstractController
{

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @Route("/", name="app_dashboard")
     */
    public function index(Request $request): Response
    {
        $year = $request->get('year');
        $month = $request->get('month');
        if ($year == "") {
            $year = date("Y");
        }

        if ($month == "") {
            $month = date("m");
        }
        $data = null;

        if ($request->getMethod() == 'POST') {
            $data = $this->getData($year, $month);
        }

        return $this->render('dashboard/index.html.twig', [
            'data' => $data,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * @Route("/download-data/{year}/{month}", name="app_download_data")
     */
    public function downloadData($year, $month): Response
    {

        $data = $this->getData($year, $month);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Valor');
        $sheet->setCellValue('B1', 'Fecha');
        $sheet->fromArray($data['Dolares'], NULL, 'A2'); 

        $writer = new Writer\Xlsx($spreadsheet);

        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $fileName = "valor dolar {$year}-{$month}";
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', "attachment;filename={$fileName}.xlsx");
        $response->headers->set('Cache-Control','max-age=0');
        return $response;
    }

    private function getData($year, $month){
        $data = null;
        $apiKey = $this->getParameter('api_key');
        $apiBaseUrl = $this->getParameter('api_url');
        
        $response = $this->client->request(
            'GET',
            "{$apiBaseUrl}dolar/{$year}/{$month}?apikey={$apiKey}&formato=json"
        );

        if($response->getStatusCode() == 200){
            $data =  $response->toArray();
        }

        return $data;
    }
}
