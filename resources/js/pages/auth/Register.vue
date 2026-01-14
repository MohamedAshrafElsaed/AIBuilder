<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Badge } from '@/components/ui/badge';
import { login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { Check, Sparkles, Zap, Shield } from 'lucide-vue-next';
</script>

<template>
    <Head title="Create Account" />

    <div class="min-h-screen flex flex-col justify-center items-center dark:bg-[#0D0D0D] px-4 py-12">
        <!-- Background subtle glow -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full dark:bg-[#F97316]/5 blur-[120px]"></div>
            <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] rounded-full dark:bg-[#F97316]/3 blur-[100px]"></div>
        </div>

        <div class="relative z-10 w-full max-w-sm">
            <!-- Logo -->
            <Link href="/" class="flex items-center justify-center gap-3 mb-8">
                <div class="flex items-center justify-center size-10 rounded-lg bg-primary">
                    <AppLogoIcon class="size-6 fill-current text-white" />
                </div>
                <span class="text-xl font-semibold dark:text-[#E5E5E5]">AI Builder</span>
                <Badge variant="preview" class="text-[10px]">Beta</Badge>
            </Link>

            <!-- Card -->
            <div class="rounded-lg border p-8 dark:bg-[#171717] dark:border-[#262626]">
                <div class="mb-6 text-center">
                    <h1 class="text-xl font-semibold mb-2 dark:text-[#E5E5E5]">
                        Create your account
                    </h1>
                    <p class="text-sm dark:text-[#737373]">
                        Start building with AI-powered development
                    </p>
                </div>

                <!-- Features List -->
                <div class="mb-6 space-y-2.5">
                    <div class="flex items-center gap-3 text-sm">
                        <div class="flex size-5 items-center justify-center rounded-full dark:bg-[#22C55E]/10">
                            <Check class="size-3 dark:text-[#22C55E]" />
                        </div>
                        <span class="dark:text-[#A3A3A3]">Unlimited projects on free tier</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="flex size-5 items-center justify-center rounded-full dark:bg-[#22C55E]/10">
                            <Sparkles class="size-3 dark:text-[#F97316]" />
                        </div>
                        <span class="dark:text-[#A3A3A3]">AI-powered code analysis</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="flex size-5 items-center justify-center rounded-full dark:bg-[#22C55E]/10">
                            <Zap class="size-3 dark:text-[#F59E0B]" />
                        </div>
                        <span class="dark:text-[#A3A3A3]">Auto stack detection</span>
                    </div>
                </div>

                <!-- OAuth Buttons -->
                <div class="space-y-3">
                    <!-- GitHub -->
                    <a
                        href="/auth/github"
                        class="flex w-full items-center justify-center gap-3 rounded-md px-4 py-3 text-sm font-medium transition-all duration-150 dark:bg-[#E5E5E5] dark:text-[#0D0D0D] dark:hover:bg-white"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z" />
                        </svg>
                        <span>Sign up with GitHub</span>
                    </a>

                    <!-- Google -->
                    <a
                        href="/auth/google"
                        class="flex w-full items-center justify-center gap-3 rounded-md border px-4 py-3 text-sm font-medium transition-all duration-150 dark:bg-transparent dark:border-[#333] dark:text-[#A3A3A3] dark:hover:bg-[#1A1A1A] dark:hover:text-[#E5E5E5]"
                    >
                        <svg class="size-5" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                        </svg>
                        <span>Sign up with Google</span>
                    </a>
                </div>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t dark:border-[#262626]"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="px-3 text-xs dark:bg-[#171717] dark:text-[#525252]">
                            No credit card required
                        </span>
                    </div>
                </div>

                <!-- Trust Indicators -->
                <div class="flex items-center justify-center gap-4 text-xs dark:text-[#525252]">
                    <div class="flex items-center gap-1.5">
                        <Shield class="size-3.5 dark:text-[#22C55E]" />
                        <span>SOC2</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Shield class="size-3.5 dark:text-[#22C55E]" />
                        <span>GDPR</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Zap class="size-3.5 dark:text-[#F59E0B]" />
                        <span>99.9% Uptime</span>
                    </div>
                </div>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm dark:text-[#737373]">
                        Already have an account?
                        <TextLink
                            :href="login()"
                            class="ml-1 font-medium dark:text-primary dark:hover:text-primary/80"
                        >
                            Sign in
                        </TextLink>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-6 text-center text-xs dark:text-[#525252]">
                By signing up, you agree to our
                <a href="#" class="dark:text-[#737373] dark:hover:text-[#A3A3A3]">Terms</a>
                and
                <a href="#" class="dark:text-[#737373] dark:hover:text-[#A3A3A3]">Privacy Policy</a>
            </p>
        </div>
    </div>
</template>
