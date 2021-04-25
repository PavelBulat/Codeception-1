<?php

declare(strict_types=1);

namespace Codeception\Subscriber;

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\PHPUnit\Compatibility\PHPUnit9;
use PHPUnit\Metadata\HookFacade;
use PHPUnit\Util\Test as TestUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function call_user_func;
use function get_class;
use function is_callable;

class BeforeAfterTest implements EventSubscriberInterface
{
    use Shared\StaticEventsTrait;

    /**
     * @var array<string, string|int[]|string[]>
     */
    protected static $events = [
        Events::SUITE_BEFORE => 'beforeClass',
        Events::SUITE_AFTER  => ['afterClass', 100]
    ];

    /**
     * @var array
     */
    protected $hooks = [];
    /**
     * @var array
     */
    protected $startedTests = [];

    public function beforeClass(SuiteEvent $event): void
    {
        foreach ($event->getSuite()->tests() as $test) {
            $testClass = get_class($test);
            if (PHPUnit9::getHookMethodsMethodExists()) {
                $this->hooks[$testClass] = TestUtil::getHookMethods($testClass);
            } else {
                $this->hooks[$testClass] = (new HookFacade)->hookMethods($testClass);
            }
        }
        $this->runHooks('beforeClass');
    }

    public function afterClass(SuiteEvent $event): void
    {
        $this->runHooks('afterClass');
    }

    protected function runHooks(string $hookName): void
    {
        foreach ($this->hooks as $className => $hook) {
            foreach ($hook[$hookName] as $method) {
                if (is_callable([$className, $method])) {
                    call_user_func([$className, $method]);
                }
            }
        }
    }
}
