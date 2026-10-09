@extends('leads.layout')

@section('title', $t('شكراً لاهتمامك بـ GlowRez', 'Thank you — GlowRez'))
@section('robots', 'noindex, nofollow')
@section('body-class', 'gl-page-thanks')

@section('content')
<div class="gl-thanks">
  <section class="gl-card gl-rise">
    @include('leads.partials.success', ['inline' => false, 'duplicate' => $duplicate, 'whatsapp_url' => $whatsapp_url])
  </section>
</div>
@endsection
