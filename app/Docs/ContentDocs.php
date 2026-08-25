<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/content/faqs",
 *     tags={"Content"},
 *     summary="List active FAQs grouped by category",
 *     description="Public endpoint. Returns an object keyed by FAQ category, each holding an array of FAQ entries.",
 *     operationId="contentFaqs",
 *     @OA\Response(
 *         response=200,
 *         description="FAQ list",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Success"),
 *             @OA\Property(property="data", type="object", example={"General":{{"id":1,"question":"What is Reco?","answer":"An offline-first accounting app.","category":"General"}}})
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/content/testimonials",
 *     tags={"Content"},
 *     summary="List active testimonials",
 *     operationId="contentTestimonials",
 *     @OA\Parameter(
 *         name="featured",
 *         in="query",
 *         required=false,
 *         description="Return only featured testimonials",
 *         @OA\Schema(type="boolean", example=true)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Testimonial list",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/content/site-settings",
 *     tags={"Content"},
 *     summary="Public site settings (branding, contact, social links)",
 *     operationId="contentSiteSettings",
 *     @OA\Response(
 *         response=200,
 *         description="Site settings key/value map",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object", example={"site_name":"Reco","site_email":"support@reco.app","primary_color":"#0d6efd"})
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/content/pages/{slug}",
 *     tags={"Content"},
 *     summary="Get a published CMS page by slug",
 *     description="Use for in-app Privacy Policy, Terms & Conditions, About Us etc.",
 *     operationId="contentPage",
 *     @OA\Parameter(
 *         name="slug",
 *         in="path",
 *         required=true,
 *         description="Page slug",
 *         @OA\Schema(type="string", example="privacy-policy")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Page content",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="slug", type="string", example="privacy-policy"),
 *                 @OA\Property(property="title", type="string", example="Privacy Policy"),
 *                 @OA\Property(property="content", type="string", example="<p>...</p>"),
 *                 @OA\Property(property="meta_title", type="string", nullable=true),
 *                 @OA\Property(property="meta_description", type="string", nullable=true),
 *                 @OA\Property(property="updated_at", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Page not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Post(
 *     path="/content/contact",
 *     tags={"Content"},
 *     summary="Submit the public contact form",
 *     description="Rate limited to 6 requests per minute per IP.",
 *     operationId="contentSubmitContact",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email", "message"},
 *             @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="phone", type="string", maxLength=20, example="+91 9876543210"),
 *             @OA\Property(property="subject", type="string", maxLength=255, example="Pricing question"),
 *             @OA\Property(property="message", type="string", maxLength=5000, example="I would like to know more about the yearly plan.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Submission stored",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Thank you for contacting us. We will get back to you shortly."),
 *             @OA\Property(property="data", type="object", @OA\Property(property="id", type="integer", example=12))
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Too many requests",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class ContentDocs
{
    //
}
