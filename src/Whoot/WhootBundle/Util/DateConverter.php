<?php

namespace Whoot\WhootBundle\Util;

class DateConverter
{
    public $date;
    public $format;
    public $modify;
    public $timezoneAdjust;

    public function __construct(\DateTime $date, $format, $modify=null, $timezoneAdjust=true)
    {
        $this->date = $date;
        $this->format = $format;
        $this->modify = $modify;
        $this->timezoneAdjust = $timezoneAdjust;
    }

    public function calculate()
    {
        if ($this->modify)
        {
            $this->date->modify($this->modify);
        }

        $time = $this->date->format('U');

        if ($this->timezoneAdjust)
        {
            $time += $this->date->getOffset();
        }

        return date($this->format, $time);
    }

	/**
	 * @return string Formatted date
	 */
	public function __toString()
	{
		return $this->calculate();
	}
}