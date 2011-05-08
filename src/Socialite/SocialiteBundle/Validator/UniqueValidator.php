<?php

namespace Limelight\LimelightBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ValidatorException;

use Limelight\LimelightBundle\Entity\ObjectManager;

/**
 * UniqueValidator
 */
class UniqueValidator extends ConstraintValidator
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Sets the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Gets the object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Indicates whether the constraint is valid
     *
     * @param Entity     $value
     * @param Constraint $constraint
     */
    public function isValid($value, Constraint $constraint)
    {
        throw new \RuntimeException('Unique constraint called.');

        if (!$this->getObjectManager()->validateUnique($value, $constraint)) {
            $this->setMessage($constraint->message, array(
                'property' => $constraint->property
            ));
            return false;
        }

        return true;
    }
}
