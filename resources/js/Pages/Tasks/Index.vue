<script setup>
  import { ref, watch, computed, onMounted } from 'vue'; // Restored onMounted
  import { useForm, router, Link } from '@inertiajs/vue3';
  import debounce from 'lodash/debounce';

  const props = defineProps({
    tasks: Object,
    filters: Object,
    auth: Object, // Restored auth prop for WebSocket scoping
  });

  const form = useForm({ title: '' });
  const search = ref(props.filters.search ?? '');
  const status = ref(props.filters.status ?? 'all');
  const selectedIds = ref([]);

  // Real-time notification container states
  const notifications = ref([]);

  // Tracks which individual task row has its comment card view visible
  const activeTaskCommentsId = ref(null);

  // Standalone form for comment ingestion mechanics
  const commentForm = useForm({
    body: '',
    commentable_id: null,
    commentable_type: 'task',
  });

  // Connect to the private user WebSocket channel when component mounts
  onMounted(() => {
    window.Echo.private(`user.${props.auth.user.id}`).listen('ReportGenerated', (e) => {
      notifications.value.push(e);

      // Auto-clear notification toast after 6 seconds
      setTimeout(() => {
        notifications.value.shift();
      }, 6000);
    });
  });

  const triggerExport = () => {
    router.post(route('tasks.export'), {}, { preserveScroll: true });
  };

  const submit = () => {
    form.post(route('tasks.store'), { onSuccess: () => form.reset('title') });
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
    selectedIds.value = [];
    filterData();
  });
  watch(
    search,
    debounce(() => {
      selectedIds.value = [];
      filterData();
    }, 300)
  );

  // Toggle visibility of the comment drawer
  const toggleCommentsDrawer = (taskId) => {
    if (activeTaskCommentsId.value === taskId) {
      activeTaskCommentsId.value = null;
    } else {
      activeTaskCommentsId.value = taskId;
      commentForm.clearErrors();
      commentForm.reset();
    }
  };

  // Dispatch comment payload over the Inertia bridge
  const submitComment = (taskId) => {
    commentForm.commentable_id = taskId;
    commentForm.post(route('comments.store'), {
      preserveScroll: true,
      onSuccess: () => commentForm.reset('body'),
    });
  };

  // Bulk operation helpers
  const isAllSelected = computed(
    () =>
      props.tasks.data.length > 0 && props.tasks.data.every((t) => selectedIds.value.includes(t.id))
  );
  const toggleSelectAll = () => {
    if (isAllSelected.value) {
      selectedIds.value = selectedIds.value.filter(
        (id) => !props.tasks.data.some((t) => t.id === id)
      );
    } else {
      selectedIds.value = [
        ...new Set([...selectedIds.value, ...props.tasks.data.map((t) => t.id)]),
      ];
    }
  };
  const triggerBulkAction = (action) => {
    router.post(
      route('tasks.bulk'),
      { ids: selectedIds.value, action },
      { preserveScroll: true, onSuccess: () => (selectedIds.value = []) }
    );
  };
</script>

<template>
  <div class="max-w-xl mx-auto mt-12 p-6 bg-slate-900 text-white rounded-xl shadow-xl relative">
    <div class="absolute top-4 right-4 z-50 space-y-2 pointer-events-none w-72">
      <div
        v-for="(note, idx) in notifications"
        :key="idx"
        class="bg-indigo-600 text-white p-3 rounded-lg shadow-2xl border border-indigo-400/30 text-xs flex flex-col gap-1 transition-all duration-300"
      >
        <div class="font-bold flex justify-between">
          <span>⚡ System Notification</span>
          <span class="opacity-60">{{ note.timestamp }}</span>
        </div>
        <p>{{ note.message }}</p>
      </div>
    </div>

    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold">SQLite Project Hub</h2>
      <button
        @click="triggerExport"
        class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
      >
        📥 Export History
      </button>
    </div>

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
        class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded text-sm font-semibold transition"
      >
        Submit
      </button>
    </form>

    <div
      v-if="selectedIds.length > 0"
      class="mb-4 bg-indigo-950 border border-indigo-500/30 p-3 rounded-lg flex items-center justify-between shadow-md"
    >
      <span class="text-xs font-semibold text-indigo-200">Selected: {{ selectedIds.length }}</span>
      <div class="flex gap-2">
        <button
          @click="triggerBulkAction('complete')"
          class="bg-indigo-700 hover:bg-indigo-600 px-2.5 py-1 text-xs rounded transition"
        >
          Mark Completed
        </button>
        <button
          @click="triggerBulkAction('delete')"
          class="bg-red-950 text-red-300 px-2.5 py-1 text-xs rounded transition"
        >
          Delete All
        </button>
      </div>
    </div>

    <div class="border-t border-slate-800 pt-4">
      <div class="flex justify-between items-center mb-3">
        <h3 class="text-sm font-semibold tracking-wider text-slate-400 uppercase">
          Stored DB Records
        </h3>
        <label
          v-if="tasks.data.length > 0"
          class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer select-none"
          ><input
            type="checkbox"
            :checked="isAllSelected"
            @change="toggleSelectAll"
            class="rounded text-indigo-600 bg-slate-900 border-slate-700 h-3.5 w-3.5"
          />
          Select Page</label
        >
      </div>

      <ul v-if="tasks.data.length > 0" class="space-y-3">
        <li
          v-for="task in tasks.data"
          :key="task.id"
          class="bg-slate-800 p-3 rounded border border-slate-700/50 transition duration-150"
        >
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
              <input
                type="checkbox"
                :value="task.id"
                v-model="selectedIds"
                class="rounded text-indigo-600 bg-slate-900 border-slate-700 h-4 w-4"
              />
              <input
                type="checkbox"
                :checked="task.is_completed"
                @change="toggleTask(task)"
                class="rounded text-indigo-600 bg-slate-900 border-slate-700 h-4 w-4"
              />
              <div>
                <p class="text-sm" :class="{ 'opacity-40 line-through': task.is_completed }">
                  {{ task.title }}
                </p>
                <div class="flex items-center gap-2 mt-0.5">
                  <span class="text-[10px] text-slate-400">By: {{ task.user.name }}</span>
                  <button
                    @click="toggleCommentsDrawer(task.id)"
                    class="text-[10px] text-indigo-400 hover:underline"
                  >
                    💬 {{ task.comments_count }}
                    {{ task.comments_count === 1 ? 'comment' : 'comments' }}
                  </button>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="text-xs text-indigo-400 bg-indigo-950 px-2 py-0.5 rounded border border-indigo-900"
                >ID: {{ task.id }}</span
              >
              <button @click="deleteTask(task.id)" class="text-slate-400 hover:text-red-400">
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
          </div>

          <div
            v-if="activeTaskCommentsId === task.id"
            class="mt-4 pt-3 border-t border-slate-700/60 bg-slate-850/40 p-2 rounded-lg"
          >
            <h4 class="text-xs font-bold text-slate-400 mb-2 uppercase tracking-wide">
              Discussion Loop
            </h4>

            <div
              v-if="task.comments.length > 0"
              class="space-y-2 mb-3 max-h-40 overflow-y-auto pr-1"
            >
              <div
                v-for="comment in task.comments"
                :key="comment.id"
                class="bg-slate-900/60 p-2 rounded border border-slate-700/30 text-xs"
              >
                <div class="flex justify-between text-slate-400 text-[10px] mb-0.5">
                  <span class="font-semibold text-indigo-300">{{ comment.user.name }}</span>
                  <span>{{ new Date(comment.created_at).toLocaleDateString() }}</span>
                </div>
                <p class="text-slate-200">{{ comment.body }}</p>
              </div>
            </div>
            <p v-else class="text-[11px] text-slate-500 italic mb-3 pl-1">
              No comments posted yet on this item workspace.
            </p>

            <form @submit.prevent="submitComment(task.id)" class="flex gap-1.5 items-start">
              <div class="flex-1">
                <input
                  v-model="commentForm.body"
                  type="text"
                  placeholder="Write a community update..."
                  class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                  :class="{ 'border-red-500': commentForm.errors.body }"
                />
                <span
                  v-if="commentForm.errors.body"
                  class="text-[10px] text-red-400 mt-0.5 block pl-1"
                  >{{ commentForm.errors.body }}</span
                >
              </div>
              <button
                type="submit"
                :disabled="commentForm.processing"
                class="bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded text-xs transition font-medium"
              >
                Post
              </button>
            </form>
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
