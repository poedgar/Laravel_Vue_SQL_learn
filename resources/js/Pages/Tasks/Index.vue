<script setup>
  import { ref, watch } from 'vue';
  import { useForm, router } from '@inertiajs/vue3';
  import debounce from 'lodash/debounce'; // Standard utility bundled by default

  // Accept the new 'filters' prop from our Laravel Controller
  const props = defineProps({
    tasks: Array,
    filters: Object,
  });

  const form = useForm({
    title: '',
  });

  // Initialize reactive local states with values already in the URL (or fallback to empty)
  const search = ref(props.filters.search ?? '');
  const status = ref(props.filters.status ?? 'all');

  const submit = () => {
    form.post(route('tasks.store'), {
      onSuccess: () => form.reset('title'),
    });
  };

  const toggleTask = (task) => {
    router.put(route('tasks.update', task.id), {}, { preserveScroll: true });
  };

  const deleteTask = (id) => {
    if (confirm('Are you sure?')) {
      router.delete(route('tasks.destroy', id), { preserveScroll: true });
    }
  };

  // This function pushes our updated state parameters to the browser address bar
  const filterData = () => {
    router.get(
      route('tasks.index'),
      { search: search.value, status: status.value },
      {
        preserveState: true, // Prevents losing input focus while typing
        preserveScroll: true,
        replace: true, // Replaces browser history state so back-button isn't flooded
      }
    );
  };

  // Watch the dropdown status select for immediate execution on change
  watch(status, () => {
    filterData();
  });

  // Watch the text field input, but run it through a 300ms debounce buffer delay
  watch(
    search,
    debounce(() => {
      filterData();
    }, 300)
  );
</script>

<template>
  <div class="max-w-xl mx-auto mt-12 p-6 bg-slate-900 text-white rounded-xl shadow-xl">
    <h2 class="text-xl font-bold mb-6">SQLite Project Hub</h2>

    <div class="flex gap-3 mb-6 bg-slate-800/50 p-3 rounded-lg border border-slate-800">
      <div class="flex-1">
        <input
          v-model="search"
          type="text"
          placeholder="Search tasks..."
          class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-sm text-white focus:outline-none focus:border-indigo-500"
        />
      </div>
      <div>
        <select
          v-model="status"
          class="bg-slate-900 border border-slate-700 rounded p-2 text-sm text-white focus:outline-none focus:border-indigo-500"
        >
          <option value="all">All Statuses</option>
          <option value="pending">Pending Only</option>
          <option value="completed">Completed Only</option>
        </select>
      </div>
    </div>

    <form @submit.prevent="submit" class="flex gap-2 mb-4">
      <input
        v-model="form.title"
        type="text"
        placeholder="Enter new element name..."
        class="flex-1 bg-slate-800 border border-slate-700 rounded p-2 text-white focus:outline-none focus:border-indigo-500"
      />
      <button
        type="submit"
        :disabled="form.processing"
        class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded text-sm font-semibold transition disabled:opacity-50"
      >
        Submit
      </button>
    </form>

    <p v-if="form.errors.title" class="text-red-400 text-xs mb-4">{{ form.errors.title }}</p>

    <div class="border-t border-slate-800 pt-4">
      <h3 class="text-sm font-semibold tracking-wider text-slate-400 uppercase mb-3">
        Stored DB Records ({{ tasks.length }})
      </h3>

      <ul v-if="tasks.length > 0" class="space-y-2">
        <li
          v-for="task in tasks"
          :key="task.id"
          class="bg-slate-800 p-3 rounded border border-slate-700/50 flex justify-between items-center transition"
          :class="{ 'opacity-50 line-through bg-slate-850': task.is_completed }"
        >
          <div class="flex items-center gap-3">
            <input
              type="checkbox"
              :checked="task.is_completed"
              @change="toggleTask(task)"
              class="rounded text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 h-4 w-4"
            />
            <span class="text-sm">{{ task.title }}</span>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="text-xs text-indigo-400 bg-indigo-950 px-2 py-0.5 rounded border border-indigo-900"
              >ID: {{ task.id }}</span
            >
            <button
              @click="deleteTask(task.id)"
              class="text-slate-400 hover:text-red-400 p-1 rounded transition"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
          </div>
        </li>
      </ul>

      <p v-else class="text-slate-500 text-sm italic">
        No matching results found for current filters.
      </p>
    </div>
  </div>
</template>
