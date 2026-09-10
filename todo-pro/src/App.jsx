import React, { useState } from 'react';

// Componente para exibir cada card de tarefa
function TaskPreview({ task, onToggleTask, onDeleteTask }) {
  return (
    <article className={`task-card ${task.completed ? 'completed' : 'pending'}`}>
      <div className="task-content">
        <div className="task-header-row">
          <h3>{task.completed ? '✨ ' : '📌 '}{task.title}</h3>
          <span className="status-badge">
            {task.completed ? '🌸 Concluída' : '🎀 Pendente'}
          </span>
        </div>
        <p>{task.description}</p>
      </div>

      <div className="task-actions">
        <button 
          type="button" 
          className="btn-status"
          onClick={() => onToggleTask(task.id)}
        >
          {task.completed ? '↺ Refazer' : '💖 Concluir'}
        </button>
        <button 
          type="button" 
          className="btn-delete"
          onClick={() => onDeleteTask(task.id)}
          title="Apagar tarefa"
        >
          🗑️ Apagar
        </button>
      </div>
    </article>
  );
}

export default function App() {
  const [tasks, setTasks] = useState([
    {
      id: 1,
      title: 'Organizar cantinho de estudos 🌸',
      description: 'Limpar a mesa e arrumar os cadernos fofos.',
      completed: true
    },
    {
      id: 2,
      title: 'Praticar componentes em React ✨',
      description: 'Criar componentes semânticos com estilos pastéis.',
      completed: false
    },
    {
      id: 3,
      title: 'Planejar as metas da semana 🎀',
      description: 'Anotar todas as tarefas importantes no To-Do Pro.',
      completed: false
    }
  ]);

  const [newTitle, setNewTitle] = useState('');
  const [newDescription, setNewDescription] = useState('');

  // Função para alternar entre concluída e pendente
  const handleToggleTask = (id) => {
    setTasks(prev =>
      prev.map(task =>
        task.id === id ? { ...task, completed: !task.completed } : task
      )
    );
  };

  // Função para remover a tarefa da lista
  const handleDeleteTask = (id) => {
    setTasks(prev => prev.filter(task => task.id !== id));
  };

  // Função para adicionar uma nova tarefa
  const handleAddTask = (e) => {
    e.preventDefault();
    if (!newTitle.trim()) return;

    const newTask = {
      id: Date.now(),
      title: newTitle,
      description: newDescription || 'Sem descrição adicional.',
      completed: false
    };

    setTasks([...tasks, newTask]);
    setNewTitle('');
    setNewDescription('');
  };

  return (
    <div className="app-container">
      <header className="app-header">
        <h1>✨ To-Do Pro ✨</h1>
        <p className="app-description">
          O seu espaço fofo e organizado para acompanhar suas tarefas! 
          Planeje o seu dia com tranquilidade, carinho e foco no que importa. 💖
        </p>
      </header>

      <main className="app-main">
        <section className="add-task-section">
          <h2>🌷 Nova tarefa</h2>
          <form onSubmit={handleAddTask} className="task-form">
            <input
              type="text"
              placeholder="Nome da tarefa fofa..."
              value={newTitle}
              onChange={(e) => setNewTitle(e.target.value)}
              required
            />
            <input
              type="text"
              placeholder="Detalhes ou lembretes (opcional)..."
              value={newDescription}
              onChange={(e) => setNewDescription(e.target.value)}
            />
            <button type="submit" className="btn-primary">
              + Nova tarefa
            </button>
          </form>
        </section>

        <section className="tasks-section">
          <h2> Minhas tarefas ({tasks.length})</h2>
          
          {tasks.length === 0 ? (
            <div className="empty-state">
              <p>Nenhuma tarefa por aqui! Que tal criar uma nova? </p>
            </div>
          ) : (
            <div className="tasks-grid">
              {tasks.map(task => (
                <TaskPreview 
                  key={task.id} 
                  task={task} 
                  onToggleTask={handleToggleTask}
                  onDeleteTask={handleDeleteTask}
                />
              ))}
            </div>
          )}
        </section>
      </main>
    </div>
  );
}