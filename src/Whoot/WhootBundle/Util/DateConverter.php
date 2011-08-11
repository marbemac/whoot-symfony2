<?php

namespace Whoot\WhootBundle\Util;

class DateConverter
{
    public $date;
    public $format;
    public $modify;
    public $timezone;
    public $calculated = null;

    public function __construct(\DateTime $date=null, $format=null, $modify=null, $timezone=null)
    {
        if ($date)
        {
            $this->date = $date;
        }
        else
        {
            $this->date = new \DateTime();
        }

        $this->format = $format;
        $this->modify = $modify;
        $this->timezone = $timezone;
    }

    public function calculate()
    {
        if ($this->modify)
        {
            $this->date->modify($this->modify);
        }

        if ($this->timezone)
        {
            date_default_timezone_set($this->timezone);
            $this->date->setTimezone(new \DateTimeZone($this->timezone));
        }

        $this->calculated = $this->date->format($this->format);
    }

	/**
	 * @return string Formatted date
	 */
	public function __toString()
	{
        if (!$this->calculated)
        {
            $this->calculate();
        }

        return $this->calculated;
	}
}