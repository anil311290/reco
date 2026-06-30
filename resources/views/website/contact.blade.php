@extends('website.layout')

@section('title', 'Contact Us - Reco')
@section('description', 'Get in touch with our support team. Contact us for sales inquiries, technical support, or any questions about Reco accounting software.')

@section('content')
<!-- Page Header -->
<section class="page-header bg-light">
    <div class="container text-center py-3">
        <h1 class="fw-bold mb-2">{{ $page->title ?? 'Contact Us' }}</h1>
        <p class="text-muted mb-0">{{ $page->meta_description ?? 'We\'d love to hear from you' }}</p>
    </div>
</section>

<!-- Contact Form & Info -->
<section class="py-5 section-padding">
    <div class="container">
        <div class="row g-5">
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="fw-bold mb-4">Send us a Message</h4>
                        
                        <div id="contactAlert" class="alert d-none" role="alert"></div>

                        <form id="contactForm" method="POST" action="{{ route('website.contact.submit') }}" class="needs-validation" novalidate>
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" required>
                                    <div class="invalid-tooltip">Please enter your name.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" required>
                                    <div class="invalid-tooltip">Please enter a valid email.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label fw-semibold small">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="+91 98765 43210">
                                </div>
                                <div class="col-md-6">
                                    <label for="subject" class="form-label fw-semibold small">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="How can we help?">
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="5" placeholder="Tell us more about your requirements..." required></textarea>
                                    <div class="invalid-tooltip">Please enter your message.</div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary px-4" id="contactBtn">
                                        <span class="btn-text"><i class="bi bi-send me-2"></i>Send Message</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-5">
                <h4 class="fw-bold mb-4">Get in Touch</h4>
                <p class="text-muted mb-4">
                    Have questions about Reco? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
                </p>

                <div class="d-flex mb-4 contact-info-item">
                    <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3 flex-shrink-0 contact-icon">
                        <i class="bi bi-envelope text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">Email Us</h6>
                        <p class="text-muted small mb-0">support@reco.app</p>
                    </div>
                </div>

                <div class="d-flex mb-4 contact-info-item">
                    <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3 flex-shrink-0 contact-icon">
                        <i class="bi bi-telephone text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">Call Us</h6>
                        <p class="text-muted small mb-0">+91 98765 43210</p>
                    </div>
                </div>

                <div class="d-flex mb-4 contact-info-item">
                    <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3 flex-shrink-0 contact-icon">
                        <i class="bi bi-geo-alt text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">Office</h6>
                        <p class="text-muted small mb-0">Mumbai, Maharashtra, India</p>
                    </div>
                </div>

                <div class="d-flex contact-info-item">
                    <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3 flex-shrink-0 contact-icon">
                        <i class="bi bi-clock text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">Business Hours</h6>
                        <p class="text-muted small mb-0">Mon - Fri: 9:00 AM - 6:00 PM IST</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <h5 class="fw-semibold">Find Us on Map</h5>
        </div>
        <div class="ratio ratio-21x9 rounded-3 shadow-sm">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241317.1568486574!2d72.672218409698!3d19.08267068572794!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sMumbai%2C%20India!5e0!3m2!1sen!2sin!4v1600000000000!5m2!1sen!2sin" 
                    style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    $('#contactForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = $('#contactBtn');
        const alertDiv = $('#contactAlert');
        
        btn.prop('disabled', true);
        btn.find('.btn-text').text('Sending...');
        btn.find('.spinner-border').removeClass('d-none');
        alertDiv.addClass('d-none');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                alertDiv.removeClass('d-none alert-danger').addClass('alert-success')
                    .html('<i class="bi bi-check-circle me-2"></i>' + response.message);
                form[0].reset();
            },
            error: function(xhr) {
                let msg = 'An error occurred. Please try again.';
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    msg = errors.join('<br>');
                }
                alertDiv.removeClass('d-none alert-success').addClass('alert-danger')
                    .html('<i class="bi bi-exclamation-circle me-2"></i>' + msg);
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.find('.btn-text').html('<i class="bi bi-send me-2"></i>Send Message');
                btn.find('.spinner-border').addClass('d-none');
            }
        });
    });
});
</script>
@endpush
