@props(['label', 'value', 'icon' => 'home', 'colour' => 'primary'])

<div class="col-xl-3 col-sm-6">
    <div class="card widget-1">
        <div class="card-body">
            <div class="widget-content">
                <div class="widget-round {{ $colour }}">
                    <div class="bg-round">
                        <svg class="svg-fill">
                            <use href="{{ asset('assets/svg/icon-sprite.svg#'.$icon) }}"></use>
                        </svg>
                    </div>
                </div>
                <div>
                    <h4>{{ $value }}</h4>
                    <span class="f-light">{{ $label }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
