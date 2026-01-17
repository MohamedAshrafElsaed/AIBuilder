/**
 * Landing Page Type Definitions
 *
 * TypeScript type definitions for landing page components,
 * props, and data structures.
 */

import { PageProps } from './index';

/**
 * Feature item displayed in the features section
 */
export interface Feature {
    id: string | number;
    title: string;
    description: string;
    icon?: string;
    iconComponent?: string;
    color?: string;
    link?: string;
}

/**
 * Hero section configuration
 */
export interface HeroConfig {
    title: string;
    subtitle?: string;
    description: string;
    primaryCta?: CtaButton;
    secondaryCta?: CtaButton;
    image?: string;
    video?: string;
    backgroundImage?: string;
}

/**
 * Call-to-action button configuration
 */
export interface CtaButton {
    text: string;
    url: string;
    variant?: 'primary' | 'secondary' | 'outline' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
    external?: boolean;
    icon?: string;
}

/**
 * CTA section configuration
 */
export interface CtaSection {
    title: string;
    description?: string;
    primaryCta: CtaButton;
    secondaryCta?: CtaButton;
    backgroundVariant?: 'default' | 'gradient' | 'image';
    backgroundImage?: string;
}

/**
 * Testimonial data structure
 */
export interface Testimonial {
    id: string | number;
    name: string;
    role?: string;
    company?: string;
    content: string;
    avatar?: string;
    rating?: number;
}

/**
 * Pricing plan structure
 */
export interface PricingPlan {
    id: string | number;
    name: string;
    description?: string;
    price: number | string;
    currency?: string;
    interval?: 'month' | 'year' | 'one-time';
    features: string[];
    highlighted?: boolean;
    cta: CtaButton;
}

/**
 * Statistics/metrics display
 */
export interface Statistic {
    id: string | number;
    label: string;
    value: string | number;
    suffix?: string;
    prefix?: string;
    description?: string;
}

/**
 * FAQ item structure
 */
export interface FaqItem {
    id: string | number;
    question: string;
    answer: string;
    category?: string;
}

/**
 * Landing page props extending base PageProps
 */
export interface LandingPageProps extends PageProps {
    hero?: HeroConfig;
    features?: Feature[];
    cta?: CtaSection;
    testimonials?: Testimonial[];
    pricing?: PricingPlan[];
    statistics?: Statistic[];
    faqs?: FaqItem[];
}

/**
 * Hero section component props
 */
export interface HeroSectionProps {
    title: string;
    subtitle?: string;
    description: string;
    primaryCta?: CtaButton;
    secondaryCta?: CtaButton;
    image?: string;
    video?: string;
    backgroundImage?: string;
    align?: 'left' | 'center' | 'right';
}

/**
 * Features section component props
 */
export interface FeaturesSectionProps {
    title?: string;
    subtitle?: string;
    description?: string;
    features: Feature[];
    columns?: 2 | 3 | 4;
    layout?: 'grid' | 'list' | 'carousel';
}

/**
 * CTA section component props
 */
export interface CtaSectionProps {
    title: string;
    description?: string;
    primaryCta: CtaButton;
    secondaryCta?: CtaButton;
    backgroundVariant?: 'default' | 'gradient' | 'image';
    backgroundImage?: string;
    align?: 'left' | 'center' | 'right';
}

/**
 * Testimonials section component props
 */
export interface TestimonialsSectionProps {
    title?: string;
    subtitle?: string;
    testimonials: Testimonial[];
    layout?: 'grid' | 'carousel' | 'masonry';
    columns?: 1 | 2 | 3;
}

/**
 * Pricing section component props
 */
export interface PricingSectionProps {
    title?: string;
    subtitle?: string;
    description?: string;
    plans: PricingPlan[];
    interval?: 'month' | 'year';
    showToggle?: boolean;
}

/**
 * Statistics section component props
 */
export interface StatisticsSectionProps {
    title?: string;
    subtitle?: string;
    statistics: Statistic[];
    columns?: 2 | 3 | 4;
    variant?: 'default' | 'minimal' | 'card';
}

/**
 * FAQ section component props
 */
export interface FaqSectionProps {
    title?: string;
    subtitle?: string;
    faqs: FaqItem[];
    categories?: string[];
    searchable?: boolean;
}

/**
 * Feature card component props
 */
export interface FeatureCardProps {
    feature: Feature;
    variant?: 'default' | 'minimal' | 'bordered' | 'elevated';
    size?: 'sm' | 'md' | 'lg';
}

/**
 * Testimonial card component props
 */
export interface TestimonialCardProps {
    testimonial: Testimonial;
    variant?: 'default' | 'minimal' | 'card';
    showRating?: boolean;
}

/**
 * Pricing card component props
 */
export interface PricingCardProps {
    plan: PricingPlan;
    highlighted?: boolean;
    variant?: 'default' | 'minimal' | 'featured';
}

/**
 * Statistic card component props
 */
export interface StatisticCardProps {
    statistic: Statistic;
    variant?: 'default' | 'minimal' | 'card';
    animated?: boolean;
}
