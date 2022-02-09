<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Gang;
use App\Entity\Pack;
use App\Entity\Shooter;
use App\Entity\Type;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ExcelController extends AbstractController
{
    /**
     * @Route("/admin/excel/form", name="excel_form", methods={"GET"})
     * @return Response
     */
    public function formAction()
    {
        return $this->render('Excel/form.html.twig');
    }

    /**
     * @Route("/admin/excel/upload", name="excel_upload", methods={"POST"})
     * @param SessionInterface $session
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function uploadAction(SessionInterface $session, EntityManagerInterface $entityManager, Request $request)
    {
        $locale = $request->get('locale');
        /* @var $uploadedFile UploadedFile */
        $uploadedFile = $request->files->get('upfile');
        $inputFileName = $uploadedFile->getPathname();
        $inputFileType = IOFactory::identify($inputFileName);
        $objReader = IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $spreadsheet = $objReader->load($inputFileName);
        $worksheet  = $spreadsheet->getActiveSheet();

        $cards = [];
        $firstRow = true;
        foreach ($worksheet ->getRowIterator() as $row) {
            // dismiss first row (titles)
            if ($firstRow) {
                $firstRow = false;
                continue;
            }

            $card = ['code' => '', 'title' => '', 'keywords' => '', 'text' => '', 'flavor' => ''];
            $cellIterator = $row->getCellIterator();
            foreach ($cellIterator as $cell) {
                $c = $cell->getColumn();
                // A:code // E:name // H:keywords // I:text // V:flavor
                switch ($c) {
                    case 'A':
                        $card['code'] = $cell->getValue();
                        break;
                    case 'B':
                        $card['pack'] = $cell->getValue();
                        break;
                    case 'C':
                        $card['number'] = $cell->getValue();
                        break;
                    case 'E':
                        $card['title'] = $cell->getValue();
                        break;
                    case 'F':
                        $card['cost'] = $cell->getValue();
                        break;
                    case 'G':
                        $card['type'] = $cell->getValue();
                        break;
                    case 'H':
                        $card['suit'] = $cell->getValue();
                        break;
                    case 'I':
                        $card['rank'] = $cell->getValue();
                        break;
                    case 'J':
                        $card['keywords'] = $cell->getValue();
                        break;
                    case 'K':
                        $card['text'] = str_replace("\n", "\r\n", $cell->getValue());
                        break;
                    case 'L':
                        $card['gang'] = $cell->getValue();
                        break;
                    case 'M':
                        $card['gang_letter'] = $cell->getValue();
                        break;
                    case 'O':
                        $card['illustrator'] = $cell->getValue();
                        break;
                    case 'P':
                        $card['flavor'] = $cell->getValue();
                        break;
                    case 'Q':
                        $card['quantity'] = $cell->getValue();
                        break;
                    case 'R':
                        $card['shooter'] = $cell->getValue();
                        break;
                }
            }
            if (count($card) && !empty($card['code'])) {
                $cards[] = $card;
            }
        }

        $session->set('trad_upload_data', $cards);
        $session->set('trad_upload_locale', $locale);

        $repo = $entityManager->getRepository(Card::class);

        foreach ($cards as $i => $card) {
            $cards[$i]['warning'] = true;
            $dbcard = $repo->findOneBy(['code' => $card['code']]);
            $cards[$i]['oldtitle'] = $dbcard ? $dbcard->getTitle($locale, true) : '';
            $cards[$i]['oldkeywords'] = $dbcard ? $dbcard->getKeywords($locale, true) : '';
            $cards[$i]['oldtext'] = $dbcard ? $dbcard->getText($locale, true) : '';
            $cards[$i]['oldflavor'] = $dbcard ? $dbcard->getFlavor($locale, true) : '';
            $cards[$i]['warning'] = ($cards[$i]['oldtitle'] && $cards[$i]['oldtitle'] != $cards[$i]['title'])
                || ($cards[$i]['oldkeywords'] && $cards[$i]['oldkeywords'] != $cards[$i]['keywords'])
                || ($cards[$i]['oldtext'] && $cards[$i]['oldtext'] != $cards[$i]['text'])
                || ($cards[$i]['oldflavor'] && $cards[$i]['oldflavor'] != $cards[$i]['flavor']);
        }

        return $this->render('Excel/confirm.html.twig', [
            'locale' => $locale,
            'cards' => $cards,
        ]);
    }

    /**
     * @Route("/admin/excel/confirm", name="excel_confirm", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface $session
     * @return Response
     */
    public function confirmAction(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $cards = $session->get('trad_upload_data');
        $locale = $session->get('trad_upload_locale');
        $repo = $entityManager->getRepository(Card::class);

        $loc = $locale != "en" ? ucfirst($locale) : "";

        foreach ($cards as $card) {
            $dbcard = $repo->findOneBy(['code' => $card['code']]);
            if (!$dbcard) {
                $dbcard = new Card();
                $dbcard->setTs(new DateTime());
            }

            $card['pack'] = $entityManager->getRepository(Pack::class)->findOneBy(["name$loc" => $card['pack']]);
            $card['type'] = $entityManager->getRepository(Type::class)->findOneBy(["name$loc" => $card['type']]);
            $card['shooter'] = $entityManager
                ->getRepository(Shooter::class)
                ->findOneBy(["name$loc" => $card['shooter']]);
            $card['gang'] = $entityManager->getRepository(Gang::class)->findOneBy(["name$loc" => $card['gang']]);

            foreach ($card as $key => $value) {
                $func = 'set' . ucfirst($key);
                $dbcard->$func($value, $locale);
            }

            $entityManager->persist($dbcard);
        }
        $entityManager->flush();

        return new Response('OK');
    }
}
