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

use Whoot\WhootBundle\Util\DateConverter;

class TagController extends ContainerAware
{
    /**
     * 
     */
    public function trendingAction()
    {
        $response = new Response();

        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($user->getCurrentLocation())
        {
            $start = new DateConverter(null, 'Y-m-d 05:00:00', '-5 hours', $user->getCurrentLocation()->getTimezone());
            $posts = $this->container->get('whoot.manager.post')->findPostsBy(
                array(
                     'isCurrentPost' => true,
                     'currentLocation.'.strtolower($user->getCurrentLocation()->getType()) => new \MongoId($user->getCurrentLocation()->getId())
                ),
                array(),
                array(),
                array('target' => 'createdAt', 'start' => $start)
            );
            
            $trendingTags = $this->container->get('whoot.manager.tag')->getTrending($posts, 10);
        }
        else
        {
            $trendingTags = array();
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Tag:trending.html.twig', array(
            'trendingTags' => $trendingTags
        ), $response);
    }

    /*
     * Make a word trendable
     */
    public function makeTrendableAction($tagId)
    {
        $tagManager = $this->container->get('whoot.manager.tag');
        $tag = $tagManager->findTagBy(array('id' => $tagId));
        $tag->setIsTrendable(true);
        $tag->setIsStopWord(false);
        $tagManager->updateTag($tag);

        $result = array();
        $result['result'] = 'success';
        $result['event'] = 'make_tag_trendable';
        $result['tagId'] = $tag->getId()->__toString();
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /*
     * Make a word a stop word
     */
    public function makeStopwordAction($tagId)
    {
        $tagManager = $this->container->get('whoot.manager.tag');
        $tag = $tagManager->findTagBy(array('id' => $tagId));
        $tag->setIsTrendable(false);
        $tag->setIsStopWord(true);
        $tagManager->updateTag($tag);

        $result = array();
        $result['result'] = 'success';
        $result['event'] = 'make_tag_stopword';
        $result['tagId'] = $tag->getId()->__toString();
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}