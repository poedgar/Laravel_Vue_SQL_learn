<script setup>
  import { ref, watch, computed } from 'vue';
  import { useForm, router, Link } from '@inertiajs/vue3';
  import debounce from 'lodash/debounce';

  const props = defineProps({
    tasks: Object,
    filters: Object,
  });

  const form = useForm({
    title: '',
  });

  const search = ref(props.filters.search ?? '');
  const status = ref(props.filters.status ?? 'all');

  // Core array state holding selected rows
  const selectedIds = ref([]);

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

  const filterData = () => {
    router.get(
      route('tasks.index'),
      { search: search.value, status: status.value },
      { preserveState: true, preserveScroll: true, replace: true }
    );
  };

  watch(status, () => {
    selectedIds.value = []; // Reset selections on filter shift
    filterData();
  });

  watch(
    search,
    debounce(() => {
      selectedIds.value = [];
      filterData();
    }, 300)
  );

  // --- BULK LOGIC ENGINE ---

  // Computes whether all tasks on the current page are checked
  const isAllSelected = computed(() => {
    if (props.tasks.data.length === 0) return false;
    return props.tasks.data.every((task) => selectedIds.value.includes(task.id));
  });

  // Toggles selection of every row on the current paginated page view
  const toggleSelectAll = () => {
    if (isAllSelected.value) {
      selectedIds.value = selectedIds.value.filter(
        (id) => !props.tasks.data.some((task) => task.id === id)
      );
    } else {
      const pageIds = props.tasks.data.map((task) => task.id);
      selectedIds.value = [...new Set([...selectedIds.value, ...pageIds])];
    }
  };

  // Dispatch selected action over the Inertia interface
  const triggerBulkAction = (action) => {
    if (
      action === 'delete' &&
      !confirm(`Are you sure you want to mass delete ${selectedIds.value.length} items?`)
    ) {
      return;
    }

    router.post(
      route('tasks.bulk'),
      {
        ids: selectedIds.value,
        action: action,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          selectedIds.value = []; // Clear choices on complete
        },
      }
    );
  };
</script>

<template>
  <div class="max-w-xl mx-auto mt-12 p-6 bg-slate-900 text-white rounded-xl shadow-xl relative">
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

    <form @submit.prevent="submit" class="mb-6 flex gap-2">
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

    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-y-4 opacity-0 scale-95"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform translate-y-4 opacity-0 scale-95"
    >
      <div
        v-if="selectedIds.length > 0"
        class="mb-4 bg-indigo-950 border border-indigo-500/30 p-3 rounded-lg flex items-center justify-between shadow-inner"
      >
        <span class="text-xs font-semibold text-indigo-200"
          >Selected records: {{ selectedIds.length }}</span
        >
        <div class="flex gap-2">
          <button
            @click="triggerBulkAction('complete')"
            class="bg-indigo-700 hover:bg-indigo-600 px-2.5 py-1 text-xs rounded transition"
          >
            Mark Completed
          </button>
          <button
            @click="triggerBulkAction('incomplete')"
            class="bg-slate-800 hover:bg-slate-700 px-2.5 py-1 text-xs rounded transition border border-slate-700"
          >
            Mark Pending
          </button>
          <button
            @click="triggerBulkAction('delete')"
            class="bg-red-950 hover:bg-red-900 border border-red-700/40 text-red-300 px-2.5 py-1 text-xs rounded transition"
          >
            Delete All
          </button>
        </div>
      </div>
    </Transition>

    <div class="border-t border-slate-800 pt-4">
      <div class="flex justify-between items-center mb-3">
        <h3 class="text-sm font-semibold tracking-wider text-slate-400 uppercase">
          Stored DB Records (Page {{ tasks.current_page }} of {{ tasks.last_page }})
        </h3>
        <label
          v-if="tasks.data.length > 0"
          class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer hover:text-slate-200 select-none"
        >
          <input
            type="checkbox"
            :checked="isAllSelected"
            @change="toggleSelectAll"
            class="rounded text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 h-3.5 w-3.5"
          />
          Select Page
        </label>
      </div>

      <ul v-if="tasks.data.length > 0" class="space-y-2">
        <li
          v-for="task in tasks.data"
          :key="task.id"
          class="bg-slate-800 p-3 rounded border border-slate-700/50 flex justify-between items-center transition"
          :class="{ 'opacity-50 line-through bg-slate-850': task.is_completed }"
        >
          <div class="flex items-center gap-3">
            <input
              type="checkbox"
              :value="task.id"
              v-model="selectedIds"
              class="rounded text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 h-4 w-4"
            />
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
        No records found matching current criteria.
      </p>

      <div v-if="tasks.links.length > 3" class="mt-6 flex flex-wrap justify-center gap-1">
        <Component
          :is="link.url ? Link : 'span'"
          v-for="(link, index) in tasks.links"
          :key="index"
          :href="link.url"
          v-html="link.label"
          class="px-3 py-1.5 text-xs rounded border transition"
          :class="{
            'bg-slate-800 text-slate-400 border-slate-700 cursor-not-allowed': !link.url,
            'bg-indigo-600 text-white border-indigo-600 font-bold': link.active,
            'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800':
              link.url && !link.active,
          }"
          preserve-scroll
        />
      </div>
    </div>
  </div>
</template>
