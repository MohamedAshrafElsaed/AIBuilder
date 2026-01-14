import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none transition-all duration-150 overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90 dark:bg-[#262626] dark:text-[#A3A3A3] dark:border-[#333]",
        destructive:
          "border-transparent bg-destructive/15 text-destructive dark:bg-[#EF4444]/15 dark:text-[#F87171]",
        success:
          "border-transparent bg-success/15 text-success dark:bg-[#22C55E]/15 dark:text-[#4ADE80]",
        warning:
          "border-transparent bg-warning/15 text-warning dark:bg-[#F59E0B]/15 dark:text-[#FBBF24]",
        outline:
          "text-foreground border-border [a&]:hover:bg-accent [a&]:hover:text-accent-foreground dark:border-[#333] dark:text-[#A3A3A3]",
        preview:
          "border-[#333] bg-[#262626] text-[#A3A3A3] text-[11px] rounded",
        ai:
          "border-primary/20 bg-primary/10 text-primary dark:border-[#F97316]/20 dark:bg-[#F97316]/10 dark:text-[#FB923C]",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
