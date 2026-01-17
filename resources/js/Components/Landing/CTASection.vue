<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface Props {
  title?: string
  description?: string
  primaryButtonText?: string
  primaryButtonHref?: string
  secondaryButtonText?: string
  secondaryButtonHref?: string
  showSecondaryButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Ready to Build Your AI-Powered Application?',
  description:
    'Start creating intelligent applications today with our powerful AI builder platform. No credit card required.',
  primaryButtonText: 'Get Started Free',
  primaryButtonHref: '/register',
  secondaryButtonText: 'View Pricing',
  secondaryButtonHref: '/pricing',
  showSecondaryButton: true,
})

const handlePrimaryClick = () => {
  // Additional tracking or analytics can be added here
  console.log('Primary CTA clicked')
}

const handleSecondaryClick = () => {
  // Additional tracking or analytics can be added here
  console.log('Secondary CTA clicked')
}
</script>

<template>
  <section class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-blue-700 py-16 sm:py-24">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
      <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>

    <!-- Content -->
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <!-- Title -->
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl md:text-5xl">
          {{ title }}
        </h2>

        <!-- Description -->
        <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100 sm:text-xl">
          {{ description }}
        </p>

        <!-- CTA Buttons -->
        <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
          <!-- Primary Button -->
          <Link
            :href="primaryButtonHref"
            @click="handlePrimaryClick"
            class="inline-flex items-center justify-center rounded-md bg-white px-8 py-3 text-base font-semibold text-blue-600 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 w-full min-w-[200px] sm:w-auto"
          >
            {{ primaryButtonText }}
            <svg
              class="ml-2 h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </Link>

          <!-- Secondary Button -->
          <Link
            v-if="showSecondaryButton"
            :href="secondaryButtonHref"
            @click="handleSecondaryClick"
            class="inline-flex items-center justify-center rounded-md border-2 border-white bg-transparent px-8 py-3 text-base font-semibold text-white shadow-sm transition-colors hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 w-full min-w-[200px] sm:w-auto"
          >
            {{ secondaryButtonText }}
          </Link>
        </div>

        <!-- Trust Indicators -->
        <div class="mt-8 flex flex-col items-center justify-center gap-4 text-sm text-blue-100 sm:flex-row sm:gap-6">
          <div class="flex items-center gap-2">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              />
            </svg>
            <span>No credit card required</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Free 14-day trial</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Cancel anytime</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
