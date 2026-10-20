<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Managers\CommandRunManagerInterface;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;

final class IndexPageTest extends TestCase
{
    public function testGroupsAndSummary(): void
    {
        $model = $this->model();
        Schedule::create([
            'command_id' => $model->id,
            'name' => 'Nightly import',
            'args' => [],
            'time_method' => 'everyMinute',
            'run' => true,
        ]);
        CommandRun::create(['command_id' => $model->id, 'state' => 1]);
        // The last runs are loaded once for the singleton
        $this->app->make(CommandRunManagerInterface::class)->flush();

        $this
            ->get(route('chronos.main'))
            ->assertOk()
            ->assertSee('<title>Commands · Chronos</title>', false)
            ->assertSeeInOrder(['Application', 'IndexTest', 'app:index-test', 'Nightly import', 'Failed'])
            ->assertSeeInOrder(['Chronos', 'chronos:free-logs'])
            ->assertSee('1</b> schedules on', false)
            ->assertSee('1</b> failing', false)
            ->assertSee(route('chronos.edit', $model))
            ->assertSee('data-theme-option="dark"', false)
            ->assertSee('href="' . route('chronos.main') . '" class="active"', false)
            ->assertSee('"chronos.theme"', false);
    }

    public function testGroupOpensOnlyItsCommands(): void
    {
        $this->model();

        $html = $this
            ->get(route('chronos.main', ['group' => 'chronos']))
            ->assertOk()
            // The group is kept by the search
            ->assertSee('<input type="hidden" name="group" value="chronos">', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/data-group="application"\s+hidden\s*>/', $html);
        $this->assertMatchesRegularExpression('/data-group="chronos"\s*>/', $html);
        $this->assertMatchesRegularExpression('/data-group-link="chronos" class="active"\s+aria-current="true"/', $html);
        $this->assertMatchesRegularExpression('/data-group-link="" class="all"\s*>/', $html);

        // All of them for an unknown group
        $html = $this
            ->get(route('chronos.main', ['group' => 'unknown']))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/data-group="[^"]+"\s+hidden/', $html);
        $this->assertMatchesRegularExpression('/data-group-link="" class="all active"\s+aria-current="true"/', $html);
    }

    public function testFiltersOnThePage(): void
    {
        $this->model();

        $html = $this
            ->get(route('chronos.main', ['search' => 'index', 'lastRunState' => 'Failed', 'runsIn' => 'Manual off']))
            ->assertOk()
            // A friendly title of the value on the button of the filter
            ->assertSee('<b>Can’t run manually</b>', false)
            // An option keeps the other filters, "Any" removes only its filter
            ->assertSee(e(route('chronos.main', ['search' => 'index', 'lastRunState' => 'Failed', 'runsIn' => 'Schedule on'])), false)
            ->assertSee(e(route('chronos.main', ['search' => 'index', 'lastRunState' => 'Failed'])), false)
            ->assertSee('Clear all')
            ->assertSee('No commands found')
            // The empty state offers to remove each filter alone
            ->assertSeeInOrder(['Try without:', '“index”', 'Failed', 'Can’t run manually', 'all filters'])
            ->getContent();

        $this->assertMatchesRegularExpression('/class="active"\s+aria-current="true"\s*>\s*<i class="cx-seg-dot failed"><\/i>\s*Failed/', $html);
        $this->assertStringNotContainsString('offcanvas', $html);

        $this
            ->get(route('chronos.main'))
            ->assertOk()
            ->assertDontSee('Clear all');
    }

    public function testCountsOfOptionsWithTheOtherFilters(): void
    {
        $model = $this->model();
        CommandRun::create(['command_id' => $model->id, 'state' => 1]);
        $this->app->make(CommandRunManagerInterface::class)->flush();

        // With the search only the command of the test: one failed, nothing else
        $html = $this
            ->get(route('chronos.main', ['search' => 'index-test']))
            ->assertOk()
            ->assertSee('Showing <b>1</b> of', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/cx-seg-dot failed"><\/i>\s*Failed\s*<span class="cx-n">1<\/span>/', $html);
        $this->assertMatchesRegularExpression('/cx-seg-dot success"><\/i>\s*Succeeded\s*<span class="cx-n">0<\/span>/', $html);
        // The chosen last run does not change its own counts
        $html = $this
            ->get(route('chronos.main', ['search' => 'index-test', 'lastRunState' => 'Success']))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/cx-seg-dot failed"><\/i>\s*Failed\s*<span class="cx-n">1<\/span>/', $html);
        $this->assertMatchesRegularExpression('/No schedules\s*<small>Not scheduled at all<\/small>\s*<\/span>\s*<span class="cx-n">0<\/span>/', $html);
    }

    public function testSortIsFlatWithGroupsAsTags(): void
    {
        $this->model();

        $this
            ->get(route('chronos.main', ['sortKey' => 'time_avg', 'sortBy' => 'desc']))
            ->assertOk()
            ->assertSee('Sort: <b>Time, average ↓</b>', false)
            ->assertDontSee('<div class="cx-group-head">', false)
            ->assertSee('<span class="cx-tag">Chronos</span>', false);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', [SubstituteBindings::class]);
    }

    private function model(): Command
    {
        // A unique class: makeCommand() requires a class once
        $this->makeCommand('IndexTest', 'app:index-test', true);

        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();

        return $this
            ->app
            ->make(CommandManager::class)
            ->getByClass('App\\Console\\Commands\\IndexTest')
            ->getModel();
    }
}
