@if ($paginator->hasPages())
    <nav class="admin-pagination__nav" role="navigation" aria-label="Навигация по страницам">
        <p>
            Показано с {{ $paginator->firstItem() }} по {{ $paginator->lastItem() }}
            из {{ $paginator->total() }}
        </p>

        <div class="admin-pagination__links">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true">Назад</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Назад</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Страница {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Вперёд</a>
            @else
                <span aria-disabled="true">Вперёд</span>
            @endif
        </div>
    </nav>
@endif
