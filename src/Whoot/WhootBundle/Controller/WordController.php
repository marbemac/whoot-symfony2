<?php

namespace Whoot\WhootBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Acl\Permission\MaskBuilder,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Whoot\WhootBundle\Entity\Post;

class WordController extends ContainerAware
{
    /**
     * 
     */
    public function trendingAction()
    {
        $response = new Response();

        $user = $this->container->get('security.context')->getToken()->getUser();
        $trendingWords = $this->container->get('whoot.manager.word')->getTrending($user->getLocation(), date('Y-m-d 05:00:00', time()-(60*60*5)), 10, array('trendable' => true));

        return $this->container->get('templating')->renderResponse('WhootBundle:Word:trending.html.twig', array(
            'trendingWords' => $trendingWords,
            'location' => $user->getLocation()
        ), $response);
    }

    /*
     * Make a word trendable
     */
    public function makeTrendableAction($wordId)
    {
        $wordManager = $this->container->get('whoot.manager.word');
        $word = $wordManager->findWordBy(null, array('id' => $wordId), true);
        $word->setTrendable(true);
        $word->setIsStopWord(false);
        $wordManager->updateWord($word);

        $result = array();
        $result['result'] = 'success';
        $result['event'] = 'make_word_trendable';
        $result['wordId'] = $word->getId();
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /*
     * Make a word a stop word
     */
    public function makeStopwordAction($wordId)
    {
        $wordManager = $this->container->get('whoot.manager.word');
        $word = $wordManager->findWordBy(null, array('id' => $wordId), true);
        $word->setTrendable(false);
        $word->setIsStopWord(true);
        $wordManager->updateWord($word);

        $result = array();
        $result['result'] = 'success';
        $result['event'] = 'make_word_stopword';
        $result['wordId'] = $word->getId();
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}