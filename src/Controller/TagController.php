<?php

namespace App\Controller;

use App\Entity\Deck;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    /**
     * @Route("/tag/add", name="tag_add", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function addAction(EntityManagerInterface $entityManager, Request $request)
    {
        $list_id = $request->get('ids');
        if (!is_array($list_id)) {
            $list_id = explode(' ', $list_id);
        }
        $list_tag = $request->get('tags');
        if (!is_array($list_tag)) {
            $list_tag = explode(' ', $list_tag);
        }
        $list_tag = array_map(function ($tag) {
            return preg_replace('/[^a-zA-Z0-9-]/', '', $tag);
        }, $list_tag);
        $response = ["success" => true];
        $repo = $entityManager->getRepository(Deck::class);
        foreach ($list_id as $id) {
            $deck = $repo->find($id);
            if (!$deck) {
                continue;
            }
            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }
            $tags = array_values(
                array_filter(
                    array_unique(
                        array_merge(
                            explode(' ', $deck->getTags()),
                            $list_tag
                        )
                    ),
                    function ($tag) {
                        return $tag != "";
                    }
                )
            );
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $entityManager->flush();
        return new Response(json_encode($response));
    }

    /**
     * @Route("/tag/remove", name="tag_remove", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function removeAction(EntityManagerInterface $entityManager, Request $request)
    {
        $list_id = $request->get('ids');
        $list_tag = $request->get('tags');
        $response = ["success" => true];
        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $entityManager->getRepository(Deck::class)->find($id);
            if (!$deck) {
                continue;
            }
            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }
            $tags = array_values(array_diff(explode(' ', $deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $entityManager->flush();
        return new Response(json_encode($response));
    }

    /**
     * @Route("/tag/clear", name="tag_clear", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function clearAction(EntityManagerInterface $entityManager, Request $request)
    {
        $list_id = $request->get('ids');
        $response = ["success" => true];
        $repo = $entityManager->getRepository(Deck::class);
        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $repo->find($id);
            if (!$deck) {
                continue;
            }
            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }
            $response['tags'][$deck->getId()] = [];
            $deck->setTags('');
        }
        $entityManager->flush();
        return new Response(json_encode($response));
    }
}
