<div class="row w-100 mx-auto mb-3">
    <div class="col">
        <button
                class="btn btn-outline-primary"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#filters"
                aria-controls="filters"
        >
            Filters
        </button>

        <div
                id="filters"
                class="offcanvas offcanvas-start"
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
                                    type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Name, signature or description"
                            >
                        </div>
                    </div>

                    <div class="row w-100 mx-auto">
                        <div class="col">
                            <label for="runsIn">
                                Runs In
                            </label>
                            <select
                                    id="runsIn"
                                    class="form-control"
                                    name="runsIn"
                            >
                                @foreach(\Tkachikov\Chronos\Enums\RunsInEnum::cases() as $case)
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
                </form>
            </div>
        </div>
    </div>
</div>