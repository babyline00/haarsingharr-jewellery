@extends('layouts.storefront')

@section('title', 'Contact Us — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-5xl">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">

                {{-- Contact info --}}
                <div>
                    <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl mb-4">Get in Touch</h1>
                    <p class="text-base text-muted mb-8 leading-relaxed">
                        For order enquiries, bespoke consultations, or simply to say hello — our
                        concierge team is here to help. We typically respond within 24 hours.
                    </p>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-champagne/10 flex items-center justify-center flex-shrink-0">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><path d="M21 11.5a8.4 8.4 0 0 1-12 7.6L4 22l1.9-5A8.4 8.4 0 1 1 21 11.5z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-widest text-muted">WhatsApp</div>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', config('storefront.contact.whatsapp')) }}" target="_blank" class="hover:text-champagne-dark transition-colors">{{ config('storefront.contact.whatsapp') }}</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-champagne/10 flex items-center justify-center flex-shrink-0">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><path d="M22 6l-10 7L2 6"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-widest text-muted">Email</div>
                                <a href="mailto:{{ config('storefront.contact.email') }}" class="hover:text-champagne-dark transition-colors">{{ config('storefront.contact.email') }}</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-champagne/10 flex items-center justify-center flex-shrink-0">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-widest text-muted">Phone</div>
                                <a href="tel:{{ config('storefront.contact.phone') }}" class="hover:text-champagne-dark transition-colors">{{ config('storefront.contact.phone') }}</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-champagne/10 flex items-center justify-center flex-shrink-0">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-widest text-muted">Showrooms</div>
                                <div class="text-charcoal-soft">{{ config('storefront.contact.address') }}</div>
                                <div class="text-xs text-muted">{{ config('storefront.contact.hours') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-8 border-t border-charcoal/5">
                        <h3 class="font-serif text-lg mb-3">Visit our boutiques</h3>
                        @if (! empty($locations))
                            <div class="space-y-2 text-sm">
                                @foreach ($locations as $location)
                                    <a href="{{ route('locations') }}" class="block hover:text-champagne-dark transition-colors">
                                        {{ $location->name }} — {{ $location->city ?? 'Pakistan' }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Contact form --}}
                <div class="card p-6 md:p-8">
                    <h2 class="font-serif text-xl mb-6">Send us a message</h2>
                    <form action="{{ route('contact.store') }}" method="post">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" required value="{{ old('name') }}" class="text-sm">
                                @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required value="{{ old('email') }}" class="text-sm">
                                @error('email') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="text-sm">
                            </div>
                            <div>
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" class="text-sm">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="type">Enquiry type</label>
                            <select id="type" name="type" class="text-sm">
                                <option value="general">General enquiry</option>
                                <option value="support">Order support</option>
                                <option value="complaint">Complaint</option>
                                <option value="wholesale">Wholesale / Trade</option>
                                <option value="press">Press / Collaboration</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="6" required class="text-sm">{{ old('message') }}</textarea>
                            @error('message') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-gold w-full">Send Enquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection