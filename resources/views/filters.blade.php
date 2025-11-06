<nav class="navbar bg-body-tertiary">
    <div class="container-fluid">
        <div class="navbar-brand">
            <a class="btn btn-link text-decoration-none" href="/">
                <h1 class="h1 m-0">{{ config('app.name') }}</h1>
            </a>
            <span class="h4 m-0 py-3 text-muted">/</span>
            <a class="btn btn-link text-decoration-none" href="{{ route('chronos.main') }}">
                <h1 class="h1 m-0">Commands</h1>
            </a>
        </div>
        <form
                class="d-flex"
                method="GET"
                action="{{ route('chronos.main') }}"
                role="search"
        >
            <input
                    id="search"
                    class="form-control me-2"
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Name, signature or etc."
            >

            <button
                    class="btn btn-outline-primary"
                    type="submit"
            >
                Search
            </button>
        </form>

        <button
                class="btn btn-outline-primary"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#filters"
                aria-controls="filters"
        >
            Filters
        </button>
    </div>
</nav>

<div class="row w-100 mx-auto mb-3">
    <div class="col">
        <div
                id="filters"
                class="offcanvas offcanvas-end"
                tabindex="-1"
                aria-labelledby="filtersLabel"
        >
            <div class="offcanvas-header">
                <h5
                        id="filtersLabel"
                        class="offcanvas-title"
                >
                    Filters
                </h5>
                <button
                        class="btn-close"
                        type="button"
                        data-bs-dismiss="offcanvas"
                        aria-label="Close"
                ></button>
            </div>
            <div class="offcanvas-body">
                <form
                        method="GET"
                        action="{{ route('chronos.main') }}"
                >
                    <div class="row w-100 mx-auto mb-3">
                        <div class="col">
                            <a
                                    class="btn btn-light w-100"
                                    href="{{ route('chronos.main') }}"
                            >
                                Reset
                            </a>
                        </div>

                        <div class="col">
                            <button
                                    class="btn btn-outline-primary w-100"
                                    type="submit"
                            >
                                Search
                            </button>
                        </div>
                    </div>

                    <div class="row w-100 mx-auto mb-3">
                        <div class="col">
                            <label for="search">
                                Search
                            </label>
                            <input
                                    id="search"
                                    class="form-control"
                                    type="search"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Name, signature or etc."
                            >
                        </div>
                    </div>

                    <div class="row w-100 mx-auto mb-3">
                        <div class="col">
                            <label for="runsIn">
                                Runs In
                            </label>
                            <select
                                    id="runsIn"
                                    class="form-control"
                                    name="runsIn"
                            >
                                @foreach(\Tkachikov\Chronos\Enums\RunsInFilterEnum::cases() as $case)
                                    <option
                                            value="{{ $case->value }}"
                                            @selected(request('runsIn') === $case->value)
                                    >
                                        {{ $case->value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row w-100 mx-auto mb-3">
                        <div class="col">
                            <label for="scheduleMethod">
                                Schedule method
                            </label>
                            <select
                                    id="scheduleMethod"
                                    class="form-control"
                                    name="scheduleMethod"
                            >
                                <option value="">All</option>
                                @foreach($times as $method => $time)
                                    <option
                                            value="{{ $method }}"
                                            @selected(request('scheduleMethod') === $method)
                                    >
                                        {{ $time->getTitle() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row w-100 mx-auto mb-3">
                        <div class="col">
                            <label for="schedulers">
                                Schedulers
                            </label>
                            <select
                                    id="schedulers"
                                    class="form-control"
                                    name="schedulers"
                            >
                                @foreach(\Tkachikov\Chronos\Enums\SchedulersFilterEnum::cases() as $case)
                                    <option
                                            value="{{ $case->value }}"
                                            @selected(request('schedulers') === $case->value)
                                    >
                                        {{ $case->value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row w-100 mx-auto">
                        <div class="col">
                            <label for="lastRunState">
                                Last run
                            </label>
                            <select
                                    id="lastRunState"
                                    class="form-control"
                                    name="lastRunState"
                            >
                                <option value="">All</option>
                                @foreach(\Tkachikov\Chronos\Enums\LastRunStateFilterEnum::cases() as $case)
                                    <option
                                            value="{{ $case->value }}"
                                            @selected(request('lastRunState') === $case->value)
                                    >
                                        {{ $case->value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>