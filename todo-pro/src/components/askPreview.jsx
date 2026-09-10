import React from 'react';

export function TaskPreview({ task, onToggleTask, onDeleteTask }) {
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