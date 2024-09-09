<?php 

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService{
    private $twig;

    public function __construct(Environment $twig){
        $this->twig = $twig;
    }
    
    public function generatePDF($html){
        $pdfOptions = new Options();//options pour le pdf
        $pdfOptions->set('defaultFont', 'Arial');//police par defaut

        $pdfFile = new Dompdf($pdfOptions);//instanciation de la classe Dompdf
        $pdfFile->loadHtml($html);//on charge le html

        $pdfFile->setPaper('A4', 'portrait');
        $pdfFile->render();
        return $pdfFile->output();
    }
}
