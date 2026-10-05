@if ($paginator->hasPages())
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>
    @endif

    {{-- Page Numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span>{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active"><span>{{ $page }}</span></span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>
    @else
        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
    @endif
@endif
