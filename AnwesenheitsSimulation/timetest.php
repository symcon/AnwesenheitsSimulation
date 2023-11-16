<?php

declare(strict_types=1);

if (defined('PHPUNIT_TESTSUITE')) {
    trait TestTime
    {
        private $currentTime = 989884800;

        public function setTime(int $Time)
        {
            $this->currentTime = $Time;
        }

        public function GetTimerInterval(string $Ident)
        {
            return parent::GetTimerInterval($Ident);
        }

        protected function getTime()
        {
            return $this->currentTime;
        }
    }
} else {
    trait TestTime
    {
        private function getTime()
        {
            return time();
        }
    }
}
