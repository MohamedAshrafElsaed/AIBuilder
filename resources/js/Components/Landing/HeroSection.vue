<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

interface HeroSectionProps {
  title: string
  subtitle?: string
  description?: string
  primaryButtonText?: string
  primaryButtonUrl?: string
  secondaryButtonText?: string
  secondaryButtonUrl?: string
  backgroundImage?: string
  backgroundGradient?: string
}

const props = withDefaults(defineProps<HeroSectionProps>(), {
  subtitle: '',
  description: '',
  primaryButtonText: 'Get Started',
  primaryButtonUrl: '/register',
  secondaryButtonText: 'Learn More',
  secondaryButtonUrl: '/about',
  backgroundImage: '',
  backgroundGradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
})

const backgroundStyle = computed(() => {
  if (props.backgroundImage) {
    return {
      backgroundImage: `url(${props.backgroundImage})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    }
  }
  return {
    background: props.backgroundGradient,
  }
})
</script>

<template>
  <section
    class="hero-section relative overflow-hidden py-20 md:py-32"
    :style="backgroundStyle"
  >
    <div class="absolute inset-0 bg-black/20"></div>

    <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-4xl text-center">
        <h1
          v-if="subtitle"
          class="mb-4 text-sm font-semibold uppercase tracking-wider text-white/90 md:text-base"
        >
          {{ subtitle }}
        </h1>

        <h2
          class="mb-6 text-4xl font-bold leading-tight text-white md:text-5xl lg:text-6xl"
        >
          {{ title }}
        </h2>

        <p
          v-if="description"
          class="mb-10 text-lg text-white/90 md:text-xl lg:text-2xl"
        >
          {{ description }}
        </p>

        <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
          <Link
            v-if="primaryButtonText && primaryButtonUrl"
            :href="primaryButtonUrl"
            class="inline-flex items-center justify-center rounded-lg bg-white px-8 py-3 text-base font-semibold text-gray-900 shadow-lg transition-all duration-200 hover:bg-gray-100 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-transparent"
          >
            {{ primaryButtonText }}
          </Link>

          <Link
            v-if="secondaryButtonText && secondaryButtonUrl"
            :href="secondaryButtonUrl"
            class="inline-flex items-center justify-center rounded-lg border-2 border-white bg-transparent px-8 py-3 text-base font-semibold text-white transition-all duration-200 hover:bg-white hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-transparent"
          >
            {{ secondaryButtonText }}
          </Link>
        </div>

        <slot name="extra" />
      </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0">
      <svg
        class="w-full text-white"
        viewBox="0 0 1440 120"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z"
          fill="currentColor"
        />
      </svg>
    </div>
  </section>
</template>

<style scoped>
.hero-section {
  min-height: 600px;
}

@media (min-width: 768px) {
  .hero-section {
    min-height: 700px;
  }
}
</style>
