<div class="flex justify-center">
    {!! QrCode::size(50)->generate($getRecord()->guest_code) !!}
</div>