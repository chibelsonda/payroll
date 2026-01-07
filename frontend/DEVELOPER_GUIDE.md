# Developer Guide - Refactored UI Components

## Quick Reference

### 🎯 New Patterns to Follow

#### 1. **User Notifications**

Always use the `useNotification` composable for user feedback:

```typescript
import { useNotification } from '@/composables/useNotification'

const notification = useNotification()

// Success message
notification.showSuccess('Operation completed successfully!')

// Error message
notification.showError('Something went wrong!')

// Warning message
notification.showWarning('Please check your input')

// Info message
notification.showInfo('New feature available')
```

#### 2. **Authentication Forms**

Use the `AuthFormCard` component wrapper:

```vue
<template>
  <AuthFormCard 
    title="Your Form Title" 
    :error="errorMessage" 
    @clear-error="errorMessage = null"
  >
    <!-- Your form content here -->
    <v-form @submit.prevent="handleSubmit">
      <!-- form fields -->
    </v-form>

    <!-- Optional footer slot -->
    <template #footer>
      <router-link to="/somewhere">Link text</router-link>
    </template>
  </AuthFormCard>
</template>
```

#### 3. **Form Validation**

Standard validation rules pattern:

```typescript
const rules = {
  required: (v: string) => !!v || 'This field is required',
  email: (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
  minLength: (v: string) => v.length >= 8 || 'Must be at least 8 characters',
  passwordMatch: (v: string) => v === form.value.password || 'Passwords must match',
}
```

#### 4. **Dashboard Layouts**

Configure dashboards via router props (see `router/index.ts`):

```typescript
{
  path: '/your-dashboard',
  component: DashboardLayout,
  props: {
    title: 'Your Dashboard',
    appBarTitle: 'Your App - Dashboard',
    menuItems: [
      { title: 'Home', to: '/path', icon: 'mdi-home' },
      { title: 'Settings', to: '/path/settings', icon: 'mdi-cog' },
    ],
  },
  children: [
    // your routes
  ]
}
```

#### 5. **Data Fetching**

Always use composables, never direct axios calls:

```typescript
// ✅ CORRECT
import { useStudents } from '@/composables/useStudents'

const { data, isLoading, error, refetch } = useStudents()
const students = computed(() => data.value?.data || [])

// ❌ WRONG
import axios from 'axios'
const response = await axios.get('/api/v1/students')
```

#### 6. **Loading States**

Always handle loading, error, and empty states:

```vue
<template>
  <!-- Loading State -->
  <div v-if="isLoading" class="text-center py-8">
    <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
    <p class="mt-4">Loading...</p>
  </div>

  <!-- Error State -->
  <v-alert v-else-if="error" type="error" class="mb-4">
    {{ error.message }}
    <template #append>
      <v-btn variant="text" @click="refetch">Retry</v-btn>
    </template>
  </v-alert>

  <!-- Content -->
  <div v-else>
    <!-- Your content here -->
  </div>
</template>
```

#### 7. **TypeScript in Vue Components**

Always use TypeScript in `<script setup>`:

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'

// Type your refs
const count = ref<number>(0)
const user = ref<User | null>(null)

// Type your props
interface Props {
  title: string
  optional?: boolean
}

const props = defineProps<Props>()

// Type your emits
const emit = defineEmits<{
  'update:modelValue': [value: string]
  'submit': []
}>()
</script>
```

### 🚫 Anti-Patterns to Avoid

#### ❌ Don't use `window.location.reload()`

```typescript
// ❌ WRONG
const refetch = () => {
  window.location.reload()
}

// ✅ CORRECT
const refetch = () => {
  const queryClient = useQueryClient()
  queryClient.invalidateQueries({ queryKey: ['your-key'] })
}
```

#### ❌ Don't duplicate state between Pinia and Vue Query

```typescript
// ❌ WRONG
const user = ref<User | null>(null)
const { data: userData } = useCurrentUser()
// Now you have two sources of truth!

// ✅ CORRECT
const { data: user } = useCurrentUser()
// Single source of truth
```

#### ❌ Don't use console.error for user-facing errors

```typescript
// ❌ WRONG
catch (error) {
  console.error('Login failed:', error)
}

// ✅ CORRECT
catch (error: any) {
  const message = error?.response?.data?.message || 'Operation failed'
  notification.showError(message)
}
```

#### ❌ Don't create separate layouts for similar dashboards

```typescript
// ❌ WRONG
AdminLayout.vue (48 lines)
StudentLayout.vue (48 lines)

// ✅ CORRECT
DashboardLayout.vue (single unified layout with props)
```

### 📁 File Organization

```
src/
├── components/
│   ├── AuthFormCard.vue          # Reusable auth form wrapper
│   ├── NotificationSnackbar.vue  # Global notification display
│   └── StudentsList.vue          # Feature-specific components
├── composables/
│   ├── index.ts                  # Export all composables
│   ├── useAuth.ts                # Auth API calls
│   ├── useNotification.ts        # Notification state
│   ├── useStudents.ts            # Students API calls
│   └── ...
├── layouts/
│   └── DashboardLayout.vue       # Unified dashboard layout
├── stores/
│   └── auth.ts                   # Auth state (token + user from Vue Query)
├── types/
│   └── auth.ts                   # TypeScript types
└── views/
    ├── Login.vue                 # Auth views
    ├── Register.vue
    ├── AdminDashboard.vue        # Dashboard views
    └── StudentDashboard.vue
```

### 🔧 Common Tasks

#### Adding a New Dashboard View

1. Create your view component in `src/views/`
2. Add route to `router/index.ts` under the appropriate dashboard
3. Use the existing `DashboardLayout` - no need to create new layouts

#### Adding a New Form

1. Use `AuthFormCard` if it's an auth-related form
2. Add proper validation rules
3. Handle loading states with `:loading` prop on buttons
4. Show errors using `useNotification`

#### Adding a New API Endpoint

1. Create composable in `src/composables/`
2. Use Vue Query (`useQuery` or `useMutation`)
3. Export from `src/composables/index.ts`
4. Use in components (never use axios directly)

### 🎨 UI/UX Best Practices

1. **Always show loading states** - Users should know when something is happening
2. **Always show error states** - Users should know when something went wrong
3. **Provide retry mechanisms** - Let users retry failed operations
4. **Use icons** - Material Design Icons (`mdi-*`) for better UX
5. **Validate forms** - Always validate user input before submission
6. **Show success feedback** - Confirm successful operations with notifications

### 🧪 Testing Checklist

When adding new features, test:

- [ ] Loading state displays correctly
- [ ] Error state displays correctly
- [ ] Success state displays correctly
- [ ] Form validation works
- [ ] Notifications appear and dismiss
- [ ] Navigation works correctly
- [ ] TypeScript types are correct (no `any` types)
- [ ] No console errors in browser
- [ ] Responsive design works on mobile

### 📚 Key Dependencies

- **Vue 3** - Composition API with `<script setup>`
- **Vuetify 3** - Material Design component framework
- **Vue Router** - Client-side routing
- **Pinia** - State management (minimal usage)
- **TanStack Vue Query** - Server state management (primary data source)
- **Axios** - HTTP client (only used in composables)
- **TypeScript** - Type safety

### 🆘 Getting Help

If you're unsure about a pattern:

1. Check existing refactored files (Login.vue, Register.vue, StudentDashboard.vue)
2. Refer to this guide
3. Check `REFACTORING_SUMMARY.md` for detailed changes
4. Follow Vue 3 Composition API best practices

---

**Last Updated**: January 7, 2026
