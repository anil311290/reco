@extends('website.layout')

@section('title', 'FAQ - Reco')
@section('description', 'Find answers to frequently asked questions about Reco accounting software, subscriptions, and features.')

@section('content')
<!-- Page Header -->
<section class="page-header bg-light">
    <div class="container text-center py-3">
        <h1 class="fw-bold mb-2">{{ $page->title ?? 'Frequently Asked Questions' }}</h1>
        <p class="text-muted mb-0">{{ $page->meta_description ?? 'Find answers to common questions about our accounting software' }}</p>
    </div>
</section>

<!-- FAQ Accordion -->
<section class="py-5 section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if(count($faqs) > 0)
                    @foreach($faqs as $category => $items)
                    @if($category && $category !== 'General')
                    <h5 class="fw-semibold text-primary mb-3 mt-4">{{ $category }}</h5>
                    @endif
                    <div class="accordion mb-4" id="faqAccordion{{ Str::slug($category ?? 'general') }}">
                        @foreach($items as $index => $faq)
                        <div class="accordion-item border-0 mb-2 shadow-sm rounded-3 overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#faq{{ $faq['id'] ?? $index }}">
                                    {{ $faq['question'] }}
                                </button>
                            </h2>
                            <div id="faq{{ $faq['id'] ?? $index }}" class="accordion-collapse collapse" 
                                 data-bs-parent="#faqAccordion{{ Str::slug($category ?? 'general') }}">
                                <div class="accordion-body text-muted small">
                                    {!! nl2br(e($faq['answer'])) !!}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                @else
                <div class="text-center py-5">
                    <i class="bi bi-question-circle fs-1 text-muted"></i>
                    <p class="text-muted mt-3">FAQs are being prepared. Check back soon!</p>
                </div>
                @endif

                <!-- Still have questions -->
                <div class="text-center mt-5 pt-4 border-top">
                    <h5 class="fw-semibold">Still have questions?</h5>
                    <p class="text-muted">Can't find the answer you're looking for? Contact our support team.</p>
                    <a href="{{ route('website.contact') }}" class="btn btn-primary">
                        <i class="bi bi-chat-dots me-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
