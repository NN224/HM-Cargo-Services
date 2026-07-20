const fs = require('fs');
let content = fs.readFileSync('src/client/pages/Shipments.tsx', 'utf8');

const badString = `                      )}
                      )}}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow"
              placeholder="الاسم الكامل للمستلم..."`;

const goodString = `                      )}
                    </td>
                  </tr>
                ))}
                {filteredShipments.length === 0 && (
                  <tr>
                    <td colSpan={6} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                      لا يوجد شحنات مسجلة
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="إضافة شحنة جديدة">
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم المستلم</label>
            <input
              type="text"
              required
              value={formData.recipientName}
              onChange={(e) => setFormData({ ...formData, recipientName: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow"
              placeholder="الاسم الكامل للمستلم..."`;

content = content.replace(badString, goodString);
fs.writeFileSync('src/client/pages/Shipments.tsx', content);
console.log("Fixed!");
