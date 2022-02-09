<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Decklist;
use App\Services\Diff;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DiffController extends AbstractController
{
    /**
     * @Route(
     *     "/{_locale}/decklists/diff/{decklist1_id}/{decklist2_id}",
     *     name="decklists_diff",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *         "decklist1_id"="\d+",
     *         "decklist2_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Diff $diff
     * @param $shortCache
     * @param $decklist1_id
     * @param $decklist2_id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function decklistDiffAction(
        EntityManagerInterface $entityManager,
        Diff $diff,
        $shortCache,
        $decklist1_id,
        $decklist2_id,
        Request $request
    ) {
        if ($decklist1_id > $decklist2_id) {
            return $this->redirect(
                $this->generateUrl(
                    'decklists_diff',
                    ['decklist1_id' => $decklist2_id, 'decklist2_id' => $decklist1_id],
                )
            );
        }
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);
        $locale = $request->getLocale();

        /* @var $d1 Decklist */
        $d1 = $entityManager->getRepository(Decklist::class)->find($decklist1_id);
        /* @var $d2 Decklist */
        $d2 = $entityManager->getRepository(Decklist::class)->find($decklist2_id);

        if (!$d1 || !$d2) {
            throw new NotFoundHttpException();
        }

        $decks = [$d1->getContent(), $d2->getContent()];

        list($listings, $intersect) = $diff->diffContents($decks);
        $cardsRepo = $entityManager->getRepository(Card::class);

        $content1 = [];
        foreach ($listings[0] as $code => $qty) {
            $card = $cardsRepo->findOneBy(['code' => $code]);
            if ($card) {
                $content1[] = [
                    'title' => $card->getTitle($locale),
                    'code' => $code,
                    'qty' => $qty,
                ];
            }
        }

        $content2 = [];
        foreach ($listings[1] as $code => $qty) {
            $card = $cardsRepo->findOneBy(['code' => $code]);
            if ($card) {
                $content2[] = [
                    'title' => $card->getTitle($locale),
                    'code' => $code,
                    'qty' => $qty,
                ];
            }
        }

        $shared = [];
        foreach ($intersect as $code => $qty) {
            $card = $cardsRepo->findOneBy(['code' => $code]);
            if ($card) {
                $shared[] = [
                    'title' => $card->getTitle($locale),
                    'code' => $code,
                    'qty' => $qty,
                ];
            }
        }

        return $this->render('Diff/decklistsDiff.html.twig', [
                'decklist1' => [
                    'gang_code' => $d1->getGang()->getCode(),
                    'name' => $d1->getName(),
                    'id' => $d1->getId(),
                    'prettyname' => $d1->getPrettyname(),
                    'content' => $content1,
                ],
                'decklist2' => [
                    'gang_code' => $d2->getGang()->getCode(),
                    'name' => $d2->getName(),
                    'id' => $d2->getId(),
                    'prettyname' => $d2->getPrettyname(),
                    'content' => $content2,
                ],
                'shared' => $shared
        ]);
    }
}
