import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all duration-150 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-2 focus-visible:ring-ring/50",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground hover:bg-primary/90 shadow-sm",
        destructive:
          "bg-destructive text-white hover:bg-destructive/90 shadow-sm",
        outline:
          "border border-border bg-transparent hover:bg-accent hover:text-accent-foreground dark:border-[#333] dark:hover:bg-[#1A1A1A]",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80 dark:bg-[#262626] dark:text-[#A3A3A3] dark:hover:bg-[#333] dark:hover:text-[#E5E5E5]",
        ghost:
          "hover:bg-accent hover:text-accent-foreground dark:hover:bg-[#1A1A1A] dark:hover:text-[#E5E5E5]",
        link: "text-primary underline-offset-4 hover:underline",
        success:
          "bg-success text-success-foreground hover:bg-success/90 shadow-sm",
        warning:
          "bg-warning text-warning-foreground hover:bg-warning/90 shadow-sm",
      },
      size: {
        "default": "h-9 px-4 py-2 has-[>svg]:px-3",
        "sm": "h-8 rounded-md gap-1.5 px-3 text-xs has-[>svg]:px-2.5",
        "lg": "h-10 rounded-md px-6 has-[>svg]:px-4",
        "xl": "h-11 rounded-md px-8 text-base has-[>svg]:px-6",
        "icon": "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
