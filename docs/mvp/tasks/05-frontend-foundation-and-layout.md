## Frontend Foundation, Layout & Navigation (Inertia + React + shadcn)

- [ ] **React + Inertia setup**
    - [ ] Configure Vite/Inertia to use React as the frontend framework.
    - [ ] Create the main `App` shell component for Inertia pages.
    - [ ] Ensure proper TypeScript support (if used) and alias configuration.

- [ ] **shadcn UI integration**
    - [ ] Install and configure shadcn components according to the project conventions.
    - [ ] Set up a base design system (colors, typography, spacing) aligned with Tailwind v4.
    - [ ] Create shared layout components:
        - [ ] Application shell composed of:
            - [ ] A primary sidebar for main navigation.
            - [ ] A top header.
            - [ ] Main content area.
        - [ ] Page header component (title, actions, breadcrumbs).
        - [ ] Reusable button, input, select, modal/dialog components.

- [ ] **Global layout and navigation**
    - [ ] Implement main navigation structure:
        - [ ] Dashboard / Overview.
        - [ ] Accounts.
        - [ ] Transactions.
        - [ ] Budgets.
    - [ ] Add active state indicators and routing between sections using Inertia links in the sidebar.
    - [ ] Design the top header layout:
        - [ ] Left side: search / command palette trigger and input.
        - [ ] Right side: profile dropdown (avatar/name) with basic account actions.

- [ ] **Theming and dark mode**
    - [ ] Ensure Tailwind dark mode is configured and works with shadcn.
    - [ ] Provide a theme toggle (light/dark) persisted per user or per browser.
