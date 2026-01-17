<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

interface Feature {
  title: string
  description: string
  icon: string
}

interface Hero {
  title: string
  subtitle: string
  description: string
}

interface CTA {
  title: string
  description: string
}

interface Props {
  features: Feature[]
  hero?: Hero
  cta?: CTA
}

defineProps<Props>()

const scrollToFeatures = (): void => {
  const featuresSection = document.getElementById('features')
  if (featuresSection) {
    featuresSection.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <Head title="Welcome to AIBuilder" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-white">
      <div
        class="absolute inset-0 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 opacity-70"
      ></div>
      <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
        <div class="text-center">
          <h1
            class="text-5xl font-extrabold tracking-tight text-slate-900 sm:text-6xl md:text-7xl"
          >
            <span class="block">Build Smarter with</span>
            <span
              class="block bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent"
            >
              AI Builder
            </span>
          </h1>
          <p
            class="mx-auto mt-6 max-w-2xl text-lg text-slate-600 sm:text-xl md:mt-8"
          >
            Transform your development workflow with AI-powered tools. Build
            modern web applications faster, smarter, and more efficiently.
          </p>
          <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a
              href="/register"
              class="inline-flex items-center rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-4 text-base font-semibold text-white shadow-lg transition-all hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              Get Started
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
            </a>
            <button
              type="button"
              @click="scrollToFeatures"
              class="inline-flex items-center rounded-lg border-2 border-slate-300 bg-white px-8 py-4 text-base font-semibold text-slate-700 transition-all hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
            >
              Learn More
            </button>
          </div>
        </div>
      </div>
      <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-white"></div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 sm:py-24">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2
            class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl md:text-5xl"
          >
            Powerful Features
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            Everything you need to build modern web applications with confidence
          </p>
        </div>

        <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="(feature, index) in features"
            :key="index"
            class="group relative overflow-hidden rounded-2xl bg-white p-8 shadow-md transition-all hover:shadow-xl"
          >
            <div
              class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-50 opacity-0 transition-opacity group-hover:opacity-100"
            ></div>
            <div class="relative">
              <div
                class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-3xl shadow-lg"
              >
                {{ feature.icon }}
              </div>
              <h3 class="mt-6 text-xl font-semibold text-slate-900">
                {{ feature.title }}
              </h3>
              <p class="mt-3 text-base text-slate-600">
                {{ feature.description }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-600 py-20">
      <div class="absolute inset-0 bg-grid-white/10"></div>
      <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl md:text-5xl">
            Ready to Get Started?
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">
            Join thousands of developers building amazing applications with AI Builder
          </p>
          <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a
              href="/register"
              class="inline-flex items-center rounded-lg bg-white px-8 py-4 text-base font-semibold text-blue-600 shadow-lg transition-all hover:bg-blue-50 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600"
            >
              Start Building Now
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
            </a>
            <a
              href="/login"
              class="inline-flex items-center rounded-lg border-2 border-white bg-transparent px-8 py-4 text-base font-semibold text-white transition-all hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600"
            >
              Sign In
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-12">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center text-slate-600">
          <p>&copy; {{ new Date().getFullYear() }} AIBuilder. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.bg-grid-white\/10 {
  background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
</style>
