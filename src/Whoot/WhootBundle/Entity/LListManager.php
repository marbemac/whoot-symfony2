<?php

namespace Whoot\WhootBundle\Entity;

use Whoot\WhootBundle\Entity\UserManager;
use Whoot\WhootBundle\Util\SlugNormalizer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Symfony\Component\Security\Core\SecurityContext;

class LListManager
{
    protected $userManager;
    protected $em;
    protected $securityContext;
    
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(UserManager $userManager, EntityManager $em, SecurityContext $securityContext)
    {
        $this->em = $em;
        $this->userManager = $userManager;
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritDoc}
     */
    public function createLList()
    {
        $list = new LList();
        $list->setStatus('Active');
        $list->setCreatedBy($this->securityContext->getToken()->getUser());

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    function deleteList(LList $list)
    {
        $list->setStatus('Deleted');
        $this->updateList($list);
        return array('result' => 'success');
    }

    public function updateList(LList $list, $andFlush = true)
    {
        $this->em->persist($list);

        if ($andFlush)
        {
            $this->em->flush();
        }
    }

    public function findListsBy(array $criteria, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('l'))
           ->from('Whoot\WhootBundle\Entity\LList', 'l');

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('l.'.$key.' = :'.$key);
        }
        $qb->setParameters($criteria);


        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $hydrateMode = $returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY;
        $lists = $query->getResult($hydrateMode);

        return $lists;
    }

    public function findListBy(array $criteria, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('l'))
           ->from('Whoot\WhootBundle\Entity\LList', 'l');

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('l.'.$key.' = :'.$key);
        }
        $qb->setParameters($criteria);

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $hydrateMode = $returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY;
        $list = $query->getResult($hydrateMode);

        return isset($list[0]) ? $list[0] : null;
    }

    public function findListUsers($listId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('l', 'lu', 'u'))
           ->from('Whoot\WhootBundle\Entity\LList', 'l')
           ->leftJoin('l.users', 'lu', 'WITH', 'lu.status = :status')
           ->leftJoin('lu.user', 'u', 'WITH', 'u.status = :status')
           ->where('l.id = :listId')
           ->setParameters(array(
               'listId' => $listId,
               'status' => 'Active'
           ));

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $listUsers = $query->getSingleResult(Query::HYDRATE_ARRAY);

        return $listUsers;
    }

    public function addUser($list, $userId)
    {
        $user = $this->userManager->getUser(array('id' => $userId));
        if (!$user)
        {
            return false;
        }

        $lu = new UserLList();
        $lu->setUser($user);
        $lu->setList($list);
        $this->em->persist($lu);
        $this->em->flush();

        return $user;
    }

    /*
     * Find a given user-list
     */
    public function findUserList($listId, $userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('ul.id'))
           ->from('Whoot\WhootBundle\Entity\UserLList', 'ul')
           ->where('ul.list = :listId')
           ->andWhere('ul.user = :userId')
           ->setParameters(array(
               'listId' => $listId,
               'userId' => $userId
           ));

        $query = $qb->getQuery();
        $userList = $query->getArrayResult();

        return isset($userList[0]) ? $userList[0] : null;
    }
}
