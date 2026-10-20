@if ($paginator->hasPages())
    <nav class="cx-pages" aria-label="Pages">
        <span>Showing <b>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</b> of <b>{{ $paginator->total() }}</b></span>
        <ul class="pagination">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">&lsaquo;</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">&lsaquo;</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">&rsaquo;</a></li>
            @else
                <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
