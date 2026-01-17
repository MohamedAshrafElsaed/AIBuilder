<script setup lang="ts">
import { computed } from 'vue';

interface Feature {
  id: string | number;
  icon: string;
  title: string;
  description: string;
}

interface Props {
  title?: string;
  subtitle?: string;
  features: Feature[];
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Features',
  subtitle: 'Everything you need to build amazing applications',
});

const hasHeader = computed(() => props.title || props.subtitle);
</script>

<template>
  <section class="features-section py-16 md:py-24">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Section Header -->
      <div v-if="hasHeader" class="text-center mb-12 md:mb-16">
        <h2
          v-if="title"
          class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4"
        >
          {{ title }}
        </h2>
        <p
          v-if="subtitle"
          class="text-lg md:text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto"
        >
          {{ subtitle }}
        </p>
      </div>

      <!-- Features Grid -->
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-10 lg:gap-12"
      >
        <div
          v-for="feature in features"
          :key="feature.id"
          class="feature-card group"
        >
          <div
            class="bg-white dark:bg-gray-800 rounded-xl p-6 md:p-8 shadow-sm hover:shadow-xl transition-all duration-300 h-full border border-gray-100 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400"
          >
            <!-- Icon -->
            <div
              class="w-12 h-12 md:w-14 md:h-14 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4 md:mb-6 group-hover:scale-110 transition-transform duration-300"
            >
              <span
                class="text-2xl md:text-3xl"
                role="img"
                :aria-label="feature.title"
              >
                {{ feature.icon }}
              </span>
            </div>

            <!-- Content -->
            <h3
              class="text-xl md:text-2xl font-semibold text-gray-900 dark:text-white mb-3 md:mb-4"
            >
              {{ feature.title }}
            </h3>
            <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
              {{ feature.description }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.features-section {
  background: linear-gradient(
    to bottom,
    rgba(249, 250, 251, 0) 0%,
    rgba(249, 250, 251, 0.5) 50%,
    rgba(249, 250, 251, 0) 100%
  );
}

.dark .features-section {
  background: linear-gradient(
    to bottom,
    rgba(17, 24, 39, 0) 0%,
    rgba(17, 24, 39, 0.5) 50%,
    rgba(17, 24, 39, 0) 100%
  );
}

.feature-card {
  animation: fadeInUp 0.6s ease-out forwards;
  opacity: 0;
}

.feature-card:nth-child(1) {
  animation-delay: 0.1s;
}

.feature-card:nth-child(2) {
  animation-delay: 0.2s;
}

.feature-card:nth-child(3) {
  animation-delay: 0.3s;
}

.feature-card:nth-child(4) {
  animation-delay: 0.4s;
}

.feature-card:nth-child(5) {
  animation-delay: 0.5s;
}

.feature-card:nth-child(6) {
  animation-delay: 0.6s;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .feature-card {
    animation: none;
    opacity: 1;
  }

  .feature-card .group-hover\:scale-110 {
    transform: none;
  }
}
</style>
