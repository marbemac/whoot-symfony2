<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Locale\Locale;
use Whoot\WhootBundle\Document\Location;

class TimezoneType extends AbstractType
{
    protected $timezones = array(
      'America/New_York'=>'Eastern',
      'America/Chicago'=>'Central',
      'America/Phoenix'=>'Mountain',
      'America/Los_Angeles'=>'Pacific'
    );

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'choices' => $this->timezones,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(array $options)
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'timezone';
    }
}
